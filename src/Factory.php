<?php

namespace Clue\React\Ami;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use InvalidArgumentException;
use React\Promise;

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

    public function createClient($url)
    {
        $parts = parse_url((strpos($url, '://') === false ? 'tcp://' : '') . $url);
        if (!$parts || !isset($parts['scheme'], $parts['host'])) {
            return Promise\reject(new InvalidArgumentException('Given URL "' . $url . '" can not be parsed'));
        }

        // use default port 5038
        if (!isset($parts['port'])) {
            $parts['port'] = 5038;
        }

        $promise = $this->connector->connect($parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'])->then(function (ConnectionInterface $stream) {
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
}
