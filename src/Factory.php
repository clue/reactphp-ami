<?php

namespace Clue\React\Ami;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use InvalidArgumentException;

class Factory
{
    private $loop;
    private $connector;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        if ($connector === null) {
            $connector = new Connector($loop);
        }

        $this->loop = $loop;
        $this->connector = $connector;
    }

    public function createClient($address = null)
    {
        $parts = $this->parseUrl($address);

        if (isset($parts['scheme']) && $parts['scheme'] !== 'tcp') {
            $parts['host'] = 'tls://' . $parts['host'];
        }

        $promise = $this->connector->connect($parts['host'] . ':' . $parts['port'])->then(function (ConnectionInterface $stream) {
            return new Client($stream);
        });

        if (isset($parts['user'])) {
            $promise = $promise->then(function (Client $client) use ($parts) {
                $sender = new ActionSender($client);

                return $sender->login($parts['user'], $parts['pass'])->then(
                    function ($response) use ($client) {
                        return $client;
                    },
                    function ($error) use ($client) {
                        $client->close();
                        throw $error;
                    }
                );
            });
        }

        return $promise;
    }

    private function parseUrl($target)
    {
        if ($target === null) {
            $target = 'tcp://127.0.0.1';
        }
        if (strpos($target, '://') === false) {
            $target = 'tcp://' . $target;
        }

        $parts = parse_url($target);
        if ($parts === false || !isset($parts['host'])) {
            throw new InvalidArgumentException('Given URL can not be parsed');
        }

        if (!isset($parts['port'])) {
            $parts['port'] = '5038';
        }

        return $parts;
    }
}
