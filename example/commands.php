<?php

use Clue\React\Ami\Factory;
use Clue\React\Ami\Client;
use Clue\React\Ami\Api;
use Clue\React\Ami\Protocol\ActionResponse;
use Clue\React\Ami\Protocol\EventMessage;
use Clue\React\Ami\Protocol\ErrorException;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(function (Client $client) use ($loop) {
    echo 'Client connected. Use STDIN to send CLI commands via asterisk AMI.' . PHP_EOL;
    $api = new Api($client);

    $api->events(false);

    $api->listCommands()->then(function (ActionResponse $response) {
        echo 'Commands: ' . implode(', ', array_keys($response->getParts())) . PHP_EOL;
    });

    $client->on('close', function() {
        echo 'Closed' . PHP_EOL;
    });

    $loop->addReadStream(STDIN, function () use ($api) {
        $line = trim(fread(STDIN, 4096));
        echo '<' . $line . PHP_EOL;

        $api->command($line)->then(
            function(ActionResponse $response) {
                echo $response->getPart('_') . PHP_EOL;
            },
            function (ErrorException $error) use ($line) {
                echo 'Error executing "' . $line . '": ' . $error->getMessage() . PHP_EOL;
            }
        );
    });
}, 'var_dump');

$loop->run();
