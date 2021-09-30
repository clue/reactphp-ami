<?php

use React\EventLoop\Loop;

require __DIR__ . '/../vendor/autoload.php';

$factory = new Clue\React\Ami\Factory();

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(function (Clue\React\Ami\Client $client) {
    echo 'Client connected. Use STDIN to send CLI commands via asterisk AMI.' . PHP_EOL;
    $sender = new Clue\React\Ami\ActionSender($client);

    $sender->events(false);

    $sender->listCommands()->then(function (Clue\React\Ami\Protocol\Response $response) {
        echo 'Commands: ' . implode(', ', array_keys($response->getFields())) . PHP_EOL;
    });

    $client->on('close', function() {
        echo 'Closed' . PHP_EOL;

        Loop::removeReadStream(STDIN);
    });

    Loop::addReadStream(STDIN, function () use ($sender) {
        $line = trim(fread(STDIN, 4096));
        echo '<' . $line . PHP_EOL;

        $sender->command($line)->then(
            function (Clue\React\Ami\Protocol\Response $response) {
                echo $response->getCommandOutput() . PHP_EOL;
            },
            function (Exception $error) use ($line) {
                echo 'Error executing "' . $line . '": ' . $error->getMessage() . PHP_EOL;
            }
        );
    });
}, function (Exception $error) {
    echo 'Connection error: ' . $error;
});
