# clue/asterisk-ami-react [![Build Status](https://travis-ci.org/clue/reactphp-asterisk-ami.png?branch=master)](https://travis-ci.org/clue/reactphp-asterisk-ami)

Simple async, event-driven access to the Asterisk Manager Interface (AMI)

The [Asterisk PBX](http://asterisk.org/) is a popular open source telephony solution
that offers a wide range of telephony features.
The [Asterisk Manager Interface (AMI)](https://wiki.asterisk.org/wiki/display/AST/The+Asterisk+Manager+TCP+IP+API)
allows you to control and monitor the PBX.
Among others, it can be used to originate a new call, execute Asterisk commands or
monitor the status of subscribers, channels or queues.

* **Async execution of Actions** -
  Send any number of actions (commands) to the asterisk in parallel and
  process their responses as soon as results come in.
  The Promise-based design provides a *sane* interface to working with out of bound responses.
* **Event-driven core** -
  Register your event handler callbacks to react to incoming events, such as an incoming call or
  a change in a subscriber state.
* **Lightweight, SOLID design** -
  Provides a thin abstraction that is [*just good enough*](http://en.wikipedia.org/wiki/Principle_of_good_enough)
  and does not get in your way.
  Future or custom actions and events require no changes to be supported.
* **Good test coverage** -
  Comes with an automated tests suite and is regularly tested against versions as old as Asterisk 1.8+

> Note: This project is in beta stage! Feel free to report any issues you encounter.

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

## License

MIT
