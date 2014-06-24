<?php

use Clue\React\Ami\Factory;
use Clue\React\Ami\Client;
use Clue\React\Ami\Api;
use Clue\React\Ami\Protocol\ActionResponse;
use Clue\React\Ami\Protocol\EventMessage;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(
    function (Client $client) use ($loop) {
        echo 'Client connected ' . PHP_EOL;

        $api = new Api($client);
        $api->events(true);

        $client->on('close', function() {
            echo 'Connection closed' . PHP_EOL;
        });

        $client->on('event', function (EventMessage $event) {
            echo 'Event: ' . $event->getName() . ': ' . $event->toJson() . PHP_EOL;
        });
    },
    function (Exception $error) {
        echo 'Connection error: ' . $error;
    }
);

$loop->run();
