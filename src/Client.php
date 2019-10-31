<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Protocol\Action;
use Clue\React\Ami\Protocol\ErrorException;
use Clue\React\Ami\Protocol\Event;
use Clue\React\Ami\Protocol\Message;
use Clue\React\Ami\Protocol\Parser;
use Clue\React\Ami\Protocol\UnexpectedMessageException;
use Evenement\EventEmitter;
use React\Promise\Deferred;
use React\Socket\ConnectionInterface;
use Exception;
use UnexpectedValueException;

/**
 * The `Client` is responsible for exchanging messages with the Asterisk Manager Interface
 * and keeps track of pending actions.
 *
 * If you want to send outgoing actions, see below for the [`ActionSender`](#actionsender) class.
 *
 * Besides defining a few methods, this interface also implements the
 * `EventEmitterInterface` which allows you to react to certain events as documented below.
 */
class Client extends EventEmitter
{
    private $stream;

    private $pending = array();
    private $ending = false;

    private $actionId = 0;

    public function __construct(ConnectionInterface $stream, Parser $parser = null)
    {
        if ($parser === null) {
            $parser = new Parser();
        }
        $this->stream = $stream;

        $that = $this;
        $this->stream->on('data', function ($chunk) use ($parser, $that) {
            try {
                $messages = $parser->push($chunk);
            } catch (UnexpectedValueException $e) {
                $that->emit('error', array($e, $that));
                return;
            }

            foreach ($messages as $message) {
                $that->handleMessage($message);
            }
        });

        $this->on('error', array($that, 'close'));

        $this->stream->on('close', array ($that, 'close'));
    }

    /**
     * Queue the given messages to be sent via AMI
     * and wait for a [`Response`](#response) object that matches the value of its "ActionID" field.
     *
     * This method is considered advanced usage and mostly used internally only.
     * Creating [`Action`](#action) objects, sending them via AMI and waiting
     * for incoming [`Response`](#response) objects is usually hidden behind the
     * [`ActionSender`](#actionsender) interface.
     *
     * If you happen to need a custom or otherwise unsupported action, you can
     * also do so manually as follows. Consider filing a PR to add new actions
     * to the [`ActionSender`](#actionsender).
     *
     * ```php
     * $action = $client->createAction('Originate', array('Channel' => …));
     * $promise = $client->request($action);
     * ```
     *
     * @param Action $message
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     */
    public function request(Action $message)
    {
        $deferred = new Deferred();

        if ($this->ending) {
            $deferred->reject(new Exception('Already ending'));
        } else {
            $out = $message->getMessageSerialized();
            //var_dump('out', $out);
            $this->stream->write($out);
            $this->pending[$message->getActionId()] = $deferred;
        }

        return $deferred->promise();
    }

    /** @internal */
    public function handleMessage(Message $message)
    {
        if ($message instanceof Event) {
            $this->emit('event', array($message));
            return;
        }
        $id = $message->getActionId();
        if (!isset($this->pending[$id])) {
            $this->emit('error', array(new UnexpectedMessageException($message), $this));
            return;
        }

        if ($message->getFieldValue('Response') === 'Error') {
            $this->pending[$id]->reject(new ErrorException($message));
        } else {
            $this->pending[$id]->resolve($message);
        }
        unset($this->pending[$id]);

        // last pending messages received => close client
        if ($this->ending && !$this->pending) {
            $this->close();
        }
    }

    /**
     * Force-close the AMI connection and reject all pending actions.
     *
     * @return void
     */
    public function close()
    {
        if ($this->stream === null) {
            return;
        }

        $this->ending = true;

        $stream = $this->stream;
        $this->stream = null;
        $stream->close();

        $this->emit('close', array($this));

        // reject all remaining/pending requests
        foreach ($this->pending as $deferred) {
            $deferred->reject(new Exception('Client closing'));
        }
        $this->pending = array();
    }

    /**
     * Soft-close the AMI connection once all pending actions are completed.
     *
     * @return void
     */
    public function end()
    {
        $this->ending = true;

        if (!$this->isBusy()) {
            $this->close();
        }
    }

    public function isBusy()
    {
        return !!$this->pending;
    }

    /**
     * Construct a custom AMI action.
     *
     * This method is considered advanced usage and mostly used internally only.
     * Creating [`Action`](#action) objects, sending them via AMI and waiting
     * for incoming [`Response`](#response) objects is usually hidden behind the
     * [`ActionSender`](#actionsender) interface.
     *
     * If you happen to need a custom or otherwise unsupported action, you can
     * also do so manually as follows. Consider filing a PR to add new actions
     * to the [`ActionSender`](#actionsender).
     *
     * A unique value will be added to "ActionID" field automatically (needed to
     * match the incoming responses).
     *
     * ```php
     * $action = $client->createAction('Originate', array('Channel' => …));
     * $promise = $client->request($action);
     * ```
     *
     * @param string $name
     * @param array $args
     * @return Action
     */
    public function createAction($name, array $args = array())
    {
        $args = array('Action' => $name, 'ActionID' => (string)++$this->actionId) + $args;

        return new Action($args);
    }
}
