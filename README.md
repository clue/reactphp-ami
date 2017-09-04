# clue/ami-react [![Build Status](https://travis-ci.org/clue/php-ami-react.svg?branch=master)](https://travis-ci.org/clue/php-ami-react)

Streaming, event-driven access to the Asterisk Manager Interface (AMI), built on top of [ReactPHP](http://reactphp.org)

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

**Table of contents**

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Factory](#factory)
    * [createClient)](#createclient)
  * [Client](#client)
    * [on()](#on)
    * [close()](#close)
    * [end()](#end)
    * [Advanced](#advanced)
      * [createAction()](#createaction)
      * [request()](#request)
  * [ActionSender](#actionsender)
    * [Actions](#actions)
    * [Processing](#processing)
    * [Custom actions](#custom-actions)
  * [Message](#message)
    * [getFieldValue()](#getfieldvalue)
    * [getFieldValues()](#getfieldvalues)
    * [getFields()](#getfields)
    * [getActionId()](#getactionid)
  * [Response](#response)
    * [getCommandOutput()](#getcommandoutput)
  * [Action](#action)
  * [Event](#event)
    * [getName()](#getname)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Quickstart example

Once [installed](#install), you can use the following code to access your local
Asterisk Telephony instance and issue some simple commands via AMI:

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$factory->createClient('user:secret@localhost')->then(function (Client $client) {
    echo 'Client connected' . PHP_EOL;
    
    $sender = new ActionSender($client);
    $sender->listCommands()->then(function (Response $response) {
        echo 'Available commands:' . PHP_EOL;
        var_dump($response);
    });
});

$loop->run();
```

See also the [examples](examples).

## Usage

### Factory

The `Factory` is responsible for creating your [`Client`](#client) instance.
It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).

```php
$loop = \React\EventLoop\Factory::create();
$factory = new Factory($loop);
```

If you need custom DNS or proxy settings, you can explicitly pass a
custom instance of the [`ConnectorInterface`](https://github.com/reactphp/socket-client#connectorinterface):

```php
$factory = new Factory($loop, $connector);
```

#### createClient()

The `createClient(string $amiUrl): PromiseInterface<Client>` method can be used to create a new [`Client`](#client).
It helps with establishing a plain TCP/IP or secure SSL/TLS connection to the AMI
and issuing an initial `login` action.

```php
$factory->createClient($amiUrl)->then(
    function (Client $client) {
        // client connected (and authenticated)
    },
    function (Exception $e) {
        // an error occured while trying to connect or authorize client
    }
);
```

The `$amiUrl` contains the host and optional port to connect to:

```php
$factory->createClient('127.0.0.1:5038');
```

> If the `$amiUrl` is `null` (or omitted) this method defaults to connecting
  to your local host (`127.0.0.1:5038`).

The above examples to not pass any authentication details, so you may have to
call `ActionSender::login()` after connecting or use the recommended shortcut
to pass a username and secret for your AMI login details like this:

```php
$factory->createClient('user:secret@localhost');
```

The `Factory` defaults to establishing a plaintext TCP connection.
If you want to connect through a secure TLS proxy, you can use the `tls` scheme:

```php
$factory->createClient('tls://user:secret@localhost:12345');
```

### Client

The `Client` is responsible for exchanging messages with the Asterisk Manager Interface
and keeps track of pending actions.

If you want to send outgoing actions, see below for the [`ActionSender`](#actionsender) class.

#### on()

The `on(string $eventName, callable $eventHandler): void` method can be used to register a new event handler.
Incoming events and errors will be forwarded to registered event handler callbacks:

```php
$client->on('event', function (Event $event) {
    // process an incoming AMI event (see below)
});
$client->on('close', function () {
    // the connection to the AMI just closed
});
$client->on('error', function (Exception $e) {
    // and error has just been detected, the connection will terminate...
});
```

#### close()

The `close(): void` method can be used to force-close the AMI connection and reject all pending actions.

#### end()

The `end(): void` method can be used to soft-close the AMI connection once all pending actions are completed.

#### Advanced

Creating [`Action`](#action) objects, sending them via AMI and waiting for incoming
[`Response`](#response) objects is usually hidden behind the [`ActionSender`](#actionsender) interface.

If you happen to need a custom or otherwise unsupported action, you can also do so manually
as follows. Consider filing a PR though :)

##### createAction()

The `createAction(string $name, array $fields): Action` method can be used to construct a custom AMI action.
A unique value will be added to "ActionID" field automatically (needed to match incoming responses).

##### request()

The `request(Action $action): PromiseInterface<Response>` method can be used to queue the given messages to be sent via AMI
and wait for a [`Response`](#response) object that matches the value of its "ActionID" field.

### ActionSender

The `ActionSender` wraps a given [`Client`](#client) instance to provide a simple way to execute common actions.
This class represents the main interface to execute actions and wait for the corresponding responses.

```php
$sender = new ActionSender($client);
```

#### Actions

All public methods resemble their respective AMI actions.

```php
$sender->ping()->then(function (Response $response) {
    // response received for ping action
});
```

Listing all available actions is out of scope here, please refer to the [class outline](src/ActionSender.php).

#### Processing

Sending actions is async (non-blocking), so you can actually send multiple action requests in parallel.
The AMI will respond to each action with a [`Response`](#response) object. The order is not guaranteed.
Sending actions uses a [Promise](https://github.com/reactphp/promise)-based interface that makes it easy to react to when an action is *fulfilled*
(i.e. either successfully resolved or rejected with an error):

```php
$sender->ping()->then(
    function (Response $response) {
        // response received for ping action
    },
    function (Exception $e) {
        // an error occured while executing the action
        
        if ($e instanceof ErrorException) {
            // we received a valid error response (such as authorization error)
            $response = $e->getResponse();
        } else {
            // we did not receive a valid response (likely a transport issue)
        }
    }
});
```

#### Custom actions

Using the `ActionSender` is not strictly necessary, but is the recommended way to execute common actions.

If you happen to need a new or otherwise unsupported action, or additional arguments,
you can also do so manually. See the advanced [`Client`](#client) usage above for details.
A PR that updates the `ActionSender` is very much appreciated :)

### Message

The `Message` is an abstract base class for the [`Response`](#response), [`Action`](#action) and [`Event`](#event) value objects.
It provides a common interface for these three message types.

Each `Message` consists of any number of fields with each having a name and one or multiple values.
Field names are matched case-insensitive. The interpretation of values is application specific.

#### getFieldValue()

The `getFieldValue(string $key): ?string` method can be used to get the first value for the given field key.
If no value was found, `null` is returned.

#### getFieldValues()

The `getFieldValues(string $key): string[]` method can be used to get a list of all values for the given field key.
If no value was found, an empty `array()` is returned.

#### getFields()

The `getFields(): array` method can be used to get an array of all fields.

#### getActionId()

The `getActionId(): string` method can be used to get the unique action ID of this message.
This is a shortcut to get the value of the "ActionID" field.

#### Response

The `Response` value object represents the incoming response received from the AMI.
It shares all properties of the [`Message`](#message) parent class.

##### getCommandOutput()

The `getCommandOutput(): ?string` method can be used to get the resulting output of
a "command" [`Action`](#action).
This value is only available if this is actually a response to a "command" action,
otherwise it defaults to `null`.

```php
$sender->command('help')->then(function (Response $response) {
    echo $response->getCommandOutput();
});
```

#### Action

The `Action` value object represents an outgoing action message to be sent to the AMI.
It shares all properties of the [`Message`](#message) parent class.

#### Event

The `Event` value object represents the incoming event received from the AMI.
It shares all properties of the [`Message`](#message) parent class.

##### getName()

The `getName(): ?string` method can be used to get the name of the event.
This is a shortcut to get the value of the "Event" field.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

```bash
$ composer require clue/ami-react:^0.3.2
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

The test suite contains both unit tests and functional integration tests.
The functional tests require access to a running Asterisk server instance
and will be skipped by default.
If you want to also run the functional tests, you need to supply *your* AMI login
details in an environment variable like this:

```bash
$ LOGIN=username:password@localhost php vendor/bin/phpunit
```

## License

MIT
