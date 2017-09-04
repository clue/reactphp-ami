<?php

namespace Clue\React\Ami;

use React\EventLoop\LoopInterface;
use React\SocketClient\ConnectorInterface;
use React\SocketClient\Connector;
use React\SocketClient\SecureConnector;
use React\Dns\Resolver\Factory as ResolverFactory;
use React\Stream\Stream;
use InvalidArgumentException;

class Factory
{
    private $loop;
    private $connector;
    private $secureConnector;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null, ConnectorInterface $secureConnector = null)
    {
        if ($connector === null) {
            $resolverFactory = new ResolverFactory();
            $connector = new Connector($loop, $resolverFactory->create('8.8.8.8', $loop));
        }
        if ($secureConnector === null) {
            $secureConnector = new SecureConnector($connector, $loop);
        }

        $this->loop = $loop;
        $this->connector = $connector;
        $this->secureConnector = $secureConnector;
    }

    public function createClient($address = null)
    {
        $parts = $this->parseUrl($address);

        $secure = (isset($parts['scheme']) && $parts['scheme'] !== 'tcp');
        $connector = $secure ? $this->secureConnector : $this->connector;

        $promise = $connector->create($parts['host'], $parts['port'])->then(function (Stream $stream) {
            return new Client($stream);
        });

        if (isset($parts['user'])) {
            $promise = $promise->then(function (Client $client) use ($parts, $secure) {
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

        if ($parts['host'] === 'localhost') {
            $parts['host'] = '127.0.0.1';
        }

        return $parts;
    }
}
