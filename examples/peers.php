<?php

use Clue\React\Ami\ActionSender;
use Clue\React\Ami\Client;
use Clue\React\Ami\Factory;
use Clue\React\Ami\Protocol\Collection;

require __DIR__ . '/../vendor/autoload.php';

$factory = new Factory();

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(function (Client $client) {
    echo 'Successfully connected' . PHP_EOL;

    $collector = new ActionSender($client);

    $collector->sipPeers()->then(function (Collection $collection) {
        var_dump('result', $collection);
        $peers = $collection->getEntryEvents();

        echo 'found ' . count($peers) . ' peers' . PHP_EOL;
    });
}, 'var_dump');
