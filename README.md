# clue/asterisk-ami-react [![Build Status](https://travis-ci.org/clue/reactphp-asterisk-ami.png?branch=master)](https://travis-ci.org/clue/reactphp-asterisk-ami)

Simple async, event-driven access to the Asterisk Manager Interface (AMI)

> Note: This project is in eary alpha stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to access your local
Asterisk Telephony instance and issue some simple commands via AMI:

```php
$factory = new Factory($loop);

$factory->createClient('username:secret@localhost')->then(function (Client $client) {
    echo 'Client connected' . PHP_EOL;
    
    $api = new Api($client);
    $api->listCommands()->then(function (Response $response) {
        echo 'Available commands:' . PHP_EOL;
        var_dump($response);
    });
});

$loop->run();
```

See also the [examples](example).

## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/asterisk-ami-react": "dev-master"
    }
}
```

> Note: This project is currently not listed on packagist.

## License

MIT
