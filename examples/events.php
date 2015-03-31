<?php

use Clue\React\Ami\Factory;
use Clue\React\Ami\Client;
use Clue\React\Ami\ActionSender;
use Clue\React\Ami\Protocol\Response;
use Clue\React\Ami\Protocol\Event;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(
    function (Client $client) use ($loop) {
        echo 'Client connected ' . PHP_EOL;

        $sender = new ActionSender($client);
        $sender->events(true);

        $client->on('close', function() {
            echo 'Connection closed' . PHP_EOL;
        });

        $client->on('event', function (Event $event) {
            echo 'Event: ' . $event->getName() . ': ' . json_encode($event->getFields()) . PHP_EOL;
        });
    },
    function (Exception $error) {
        echo 'Connection error: ' . $error;
    }
);

$loop->run();
