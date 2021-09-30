<?php

require __DIR__ . '/../vendor/autoload.php';

$factory = new Clue\React\Ami\Factory();

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(
    function (Clue\React\Ami\Client $client) {
        echo 'Client connected ' . PHP_EOL;

        $sender = new Clue\React\Ami\ActionSender($client);
        $sender->events(true);

        $client->on('close', function() {
            echo 'Connection closed' . PHP_EOL;
        });

        $client->on('event', function (Clue\React\Ami\Protocol\Event $event) {
            echo 'Event: ' . $event->getName() . ': ' . json_encode($event->getFields()) . PHP_EOL;
        });
    },
    function (Exception $error) {
        echo 'Connection error: ' . $error;
    }
);
