<?php

use Clue\React\Ami\Factory;
use Clue\React\Ami\Client;
use Clue\React\Ami\ActionSender;
use Clue\React\Ami\Collector;
use Clue\React\Ami\Protocol\Collection;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$target = isset($argv[1]) ? $argv[1] : 'name:password@localhost';

$factory->createClient($target)->then(function (Client $client) use ($loop) {
    echo 'Successfully connected' . PHP_EOL;

    $collector = new Collector($client);

    $collector->sipPeers()->then(function (Collection $collection) {
        var_dump('result', $collection);
        $peers = $collection->getEntryEvents();

        echo 'found ' . count($peers) . ' peers' . PHP_EOL;
    });
}, 'var_dump');

$loop->run();
