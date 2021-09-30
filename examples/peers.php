<?php

require __DIR__ . '/../vendor/autoload.php';

$factory = new Clue\React\Ami\Factory();

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(function (Clue\React\Ami\Client $client) {
    echo 'Successfully connected' . PHP_EOL;

    $collector = new Clue\React\Ami\ActionSender($client);

    $collector->sipPeers()->then(function (Clue\React\Ami\Protocol\Collection $collection) {
        var_dump('result', $collection);
        $peers = $collection->getEntryEvents();

        echo 'found ' . count($peers) . ' peers' . PHP_EOL;
    });
}, function (Exception $error) {
    echo 'Connection error: ' . $error;
});
