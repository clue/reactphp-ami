<?php

namespace Clue\React\Ami;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;

/**
 * The `Factory` is responsible for creating your [`Client`](#client) instance.
 *
 * ```php
 * $factory = new Clue\React\Ami\Factory();
 * ```
 *
 * This class takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use for this object. You can use a `null` value
 * here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
 * This value SHOULD NOT be given unless you're sure you want to explicitly use a
 * given event loop instance.
 *
 * If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
 * proxy servers etc.), you can explicitly pass a custom instance of the
 * [`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface):
 *
 * ```php
 * $connector = new React\Socket\Connector(array(
 *     'dns' => '127.0.0.1',
 *     'tcp' => array(
 *         'bindto' => '192.168.10.1:0'
 *     ),
 *     'tls' => array(
 *         'verify_peer' => false,
 *         'verify_peer_name' => false
 *     )
 * ));
 *
 * $factory = new Clue\React\Ami\Factory(null, $connector);
 * ```
 */
class Factory
{
    private $connector;

    /**
     * @param ?LoopInterface $loop
     * @param ?ConnectorInterface $connector
     */
    public function __construct($loop = null, $connector = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { // manual type check to support legacy PHP < 7.1
            throw new \InvalidArgumentException('Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        }
        if ($connector !== null && !$connector instanceof ConnectorInterface) { // manual type check to support legacy PHP < 7.1
            throw new \InvalidArgumentException('Argument #2 ($connector) expected null|React\Socket\ConnectorInterface');
        }

        if ($connector === null) {
            $connector = new Connector(array(), $loop);
        }

        $this->connector = $connector;
    }

    /**
     * Create a new [`Client`](#client).
     *
     * It helps with establishing a plain TCP/IP or secure TLS connection to the AMI
     * and optionally issuing an initial `login` action.
     *
     * ```php
     * $factory->createClient($url)->then(
     *     function (Clue\React\Ami\Client $client) {
     *         // client connected (and authenticated)
     *     },
     *     function (Exception $e) {
     *         // an error occurred while trying to connect or authorize client
     *     }
     * );
     * ```
     *
     * The method returns a [Promise](https://github.com/reactphp/promise) that will
     * resolve with the [`Client`](#client) instance on success or will reject with an
     * `Exception` if the URL is invalid or the connection or authentication fails.
     *
     * The `$url` parameter contains the host and optional port (which defaults to
     * `5038` for plain TCP/IP connections) to connect to:
     *
     * ```php
     * $factory->createClient('localhost:5038');
     * ```
     *
     * The above example does not pass any authentication details, so you may have to
     * call `ActionSender::login()` after connecting or use the recommended shortcut
     * to pass a username and secret for your AMI login details like this:
     *
     * ```php
     * $factory->createClient('user:secret@localhost');
     * ```
     *
     * Note that both the username and password must be URL-encoded (percent-encoded)
     * if they contain special characters:
     *
     * ```php
     * $user = 'he:llo';
     * $pass = 'p@ss';
     *
     * $promise = $factory->createClient(
     *     rawurlencode($user) . ':' . rawurlencode($pass) . '@localhost'
     * );
     * ```
     *
     * The `Factory` defaults to establishing a plaintext TCP connection.
     * If you want to create a secure TLS connection, you can use the `tls` scheme
     * (which defaults to port `5039`):
     *
     * ```php
     * $factory->createClient('tls://user:secret@localhost:5039');
     * ```
     *
     * @param string $url
     * @return \React\Promise\PromiseInterface<Client,\Exception>
     */
    public function createClient($url)
    {
        $parts = parse_url((strpos($url, '://') === false ? 'tcp://' : '') . $url);
        if (!$parts || !isset($parts['scheme'], $parts['host'])) {
            return \React\Promise\reject(new \InvalidArgumentException('Given URL "' . $url . '" can not be parsed'));
        }

        // use default port 5039 for `tls://` or 5038 otherwise
        if (!isset($parts['port'])) {
            $parts['port'] = $parts['scheme'] === 'tls' ? 5039 : 5038;
        }

        $promise = $this->connector->connect($parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'])->then(function (ConnectionInterface $stream) {
            return new Client($stream);
        });

        if (isset($parts['user'])) {
            $promise = $promise->then(function (Client $client) use ($parts) {
                $sender = new ActionSender($client);

                return $sender->login(rawurldecode($parts['user']), rawurldecode($parts['pass']))->then(
                    function () use ($client) {
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
