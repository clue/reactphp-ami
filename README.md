# clue/reactphp-ami

[![CI status](https://github.com/clue/reactphp-ami/workflows/CI/badge.svg)](https://github.com/clue/reactphp-ami/actions)
[![installs on Packagist](https://img.shields.io/packagist/dt/clue/ami-react?color=blue&label=installs%20on%20Packagist)](https://packagist.org/packages/clue/ami-react)

Streaming, event-driven access to the Asterisk Manager Interface (AMI),
built on top of [ReactPHP](https://reactphp.org).

The [Asterisk PBX](https://www.asterisk.org/) is a popular open source telephony solution
that offers a wide range of telephony features.
The [Asterisk Manager Interface (AMI)](https://wiki.asterisk.org/wiki/display/AST/The+Asterisk+Manager+TCP+IP+API)
allows you to control and monitor the PBX.
Among others, it can be used to originate a new call, execute Asterisk commands or
monitor the status of subscribers, channels or queues.

* **Async execution of Actions** -
  Send any number of actions (commands) to the Asterisk service in parallel and
  process their responses as soon as results come in.
  The Promise-based design provides a *sane* interface to working with out of order responses.
* **Event-driven core** -
  Register your event handler callbacks to react to incoming events, such as an incoming call or
  a change in a subscriber state.
* **Lightweight, SOLID design** -
  Provides a thin abstraction that is [*just good enough*](https://en.wikipedia.org/wiki/Principle_of_good_enough)
  and does not get in your way.
  Future or custom actions and events require no changes to be supported.
* **Good test coverage** -
  Comes with an automated tests suite and is regularly tested in the *real world*
  against current Asterisk versions and versions as old as Asterisk 1.8.

**Table of contents**

* [Support us](#support-us)
* [Quickstart example](#quickstart-example)
* [Usage](#usage)
    * [Factory](#factory)
        * [createClient()](#createclient)
    * [Client](#client)
        * [close()](#close)
        * [end()](#end)
        * [createAction()](#createaction)
        * [request()](#request)
        * [event event](#event-event)
        * [error event](#error-event)
        * [close event](#close-event)
    * [ActionSender](#actionsender)
        * [Actions](#actions)
        * [Promises](#promises)
        * [Blocking](#blocking)
    * [Message](#message)
        * [getFieldValue()](#getfieldvalue)
        * [getFieldValues()](#getfieldvalues)
        * [getFieldVariables()](#getfieldvariables)
        * [getFields()](#getfields)
        * [getActionId()](#getactionid)
    * [Response](#response)
        * [getCommandOutput()](#getcommandoutput)
    * [Collection](#collection)
        * [getEntryEvents()](#getentryevents)
        * [getCompleteEvent()](#getcompleteevent)
    * [Action](#action)
        * [getMessageSerialized()](#getmessageserialized)
    * [Event](#event)
        * [getName()](#getname)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Support us

We invest a lot of time developing, maintaining and updating our awesome
open-source projects. You can help us sustain this high-quality of our work by
[becoming a sponsor on GitHub](https://github.com/sponsors/clue). Sponsors get
numerous benefits in return, see our [sponsoring page](https://github.com/sponsors/clue)
for details.

Let's take these projects to the next level together! 🚀

## Quickstart example

Once [installed](#install), you can use the following code to access your local
Asterisk instance and issue some simple commands via AMI:

```php
$factory = new Clue\React\Ami\Factory();

$factory->createClient('user:secret@localhost')->then(function (Clue\React\Ami\Client $client) {
    echo 'Client connected' . PHP_EOL;

    $sender = new Clue\React\Ami\ActionSender($client);
    $sender->listCommands()->then(function (Clue\React\Ami\Protocol\Response $response) {
        echo 'Available commands:' . PHP_EOL;
        var_dump($response);
    });
});
```

See also the [examples](examples).

## Usage

### Factory

The `Factory` is responsible for creating your [`Client`](#client) instance.

```php
$factory = new Clue\React\Ami\Factory();
```

This class takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use for this object. You can use a `null` value
here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
This value SHOULD NOT be given unless you're sure you want to explicitly use a
given event loop instance.

If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface):

```php
$connector = new React\Socket\Connector(null, array(
    'dns' => '127.0.0.1',
    'tcp' => array(
        'bindto' => '192.168.10.1:0'
    ),
    'tls' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$factory = new Clue\React\Ami\Factory(null, $connector);
```

#### createClient()

The `createClient(string $url): PromiseInterface<Client,Exception>` method can be used to
create a new [`Client`](#client).

It helps with establishing a plain TCP/IP or secure TLS connection to the AMI
and optionally issuing an initial `login` action.

```php
$factory->createClient($url)->then(
    function (Clue\React\Ami\Client $client) {
        // client connected (and authenticated)
    },
    function (Exception $e) {
        // an error occured while trying to connect or authorize client
    }
);
```

The method returns a [Promise](https://github.com/reactphp/promise) that will
resolve with the [`Client`](#client) instance on success or will reject with an
`Exception` if the URL is invalid or the connection or authentication fails.

The `$url` parameter contains the host and optional port (which defaults to
`5038` for plain TCP/IP connections) to connect to:

```php
$factory->createClient('localhost:5038');
```

The above example does not pass any authentication details, so you may have to
call `ActionSender::login()` after connecting or use the recommended shortcut
to pass a username and secret for your AMI login details like this:

```php
$factory->createClient('user:secret@localhost');
```

Note that both the username and password must be URL-encoded (percent-encoded)
if they contain special characters:

```php
$user = 'he:llo';
$pass = 'p@ss';

$promise = $factory->createClient(
    rawurlencode($user) . ':' . rawurlencode($pass) . '@localhost'
);
```

The `Factory` defaults to establishing a plaintext TCP connection.
If you want to create a secure TLS connection, you can use the `tls` scheme
(which defaults to port `5039`):

```php
$factory->createClient('tls://user:secret@localhost:5039');
```

### Client

The `Client` is responsible for exchanging messages with the Asterisk Manager Interface
and keeps track of pending actions.

If you want to send outgoing actions, see below for the [`ActionSender`](#actionsender) class.

Besides defining a few methods, this interface also implements the
`EventEmitterInterface` which allows you to react to certain events as documented below.

#### close()

The `close(): void` method can be used to
force-close the AMI connection and reject all pending actions.

#### end()

The `end(): void` method can be used to
soft-close the AMI connection once all pending actions are completed.

#### createAction()

The `createAction(string $name, array $fields): Action` method can be used to
construct a custom AMI action.

This method is considered advanced usage and mostly used internally only.
Creating [`Action`](#action) objects, sending them via AMI and waiting
for incoming [`Response`](#response) objects is usually hidden behind the
[`ActionSender`](#actionsender) interface.

If you happen to need a custom or otherwise unsupported action, you can
also do so manually as follows. Consider filing a PR to add new actions
to the [`ActionSender`](#actionsender).

A unique value will be added to "ActionID" field automatically (needed to
match the incoming responses).

```php
$action = $client->createAction('Originate', array('Channel' => …));
$promise = $client->request($action);
```

#### request() 

The `request(Action $action): PromiseInterface<Response,Exception>` method can be used to
queue the given messages to be sent via AMI
and wait for a [`Response`](#response) object that matches the value of its "ActionID" field.

This method is considered advanced usage and mostly used internally only.
Creating [`Action`](#action) objects, sending them via AMI and waiting
for incoming [`Response`](#response) objects is usually hidden behind the
[`ActionSender`](#actionsender) interface.

If you happen to need a custom or otherwise unsupported action, you can
also do so manually as follows. Consider filing a PR to add new actions
to the [`ActionSender`](#actionsender).

```php
$action = $client->createAction('Originate', array('Channel' => …));
$promise = $client->request($action);
```

#### event event

The `event` event (*what a lovely name*) will be emitted whenever AMI sends an event, such as
a phone call that just started or ended and much more.
The event receives a single [`Event`](#event) argument describing the event instance.

```php
$client->on('event', function (Clue\React\Ami\Protocol\Event $event) {
    // process an incoming AMI event (see below)
    var_dump($event->getName(), $event);
});
```

Event reporting can be turned on/off via AMI configuration and the [`events()` action](#actions).
The [`events()` action](#actions) can also be used to enable an "EventMask" to
only report certain events as per the [AMI documentation](https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Events).

See also [AMI Events documentation](https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+AMI+Events)
for more details about event types and their respective fields.

#### error event

The `error` event will be emitted once a fatal error occurs, such as
when the client connection is lost or is invalid.
The event receives a single `Exception` argument for the error instance.

```php
$client->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

This event will only be triggered for fatal errors and will be followed
by closing the client connection. It is not to be confused with "soft"
errors caused by invalid commands.

#### close event

The `close` event will be emitted once the client connection closes (terminates).

```php
$client->on('close', function () {
    echo 'Connection closed' . PHP_EOL;
});
```

See also the [`close()`](#close) method.

### ActionSender

The `ActionSender` wraps a given [`Client`](#client) instance to provide a simple way to execute common actions.
This class represents the main interface to execute actions and wait for the corresponding responses.

```php
$sender = new Clue\React\Ami\ActionSender($client);
```

#### Actions

All public methods resemble their respective AMI actions.

```php
$sender->login($name, $pass);
$sender->logoff();
$sender->ping();
$sender->command($command);
$sender->events($eventMask);

$sender->coreShowChannels();
$sender->sipPeers();
$sender->agents();

// many more…
```

Listing all available actions is out of scope here, please refer to the [class outline](src/ActionSender.php).

Note that using the `ActionSender` is not strictly necessary, but is the recommended way to execute common actions.

If you happen to need a custom or otherwise unsupported action, you can
also do so manually. See the advanced [`createAction()`](#createaction) usage above for details.
Consider filing a PR to add new actions to the `ActionSender`.

#### Promises

Sending actions is async (non-blocking), so you can actually send multiple
action requests in parallel.
The AMI will respond to each action with a [`Response`](#response) object.
The order is not guaranteed.
Sending actions uses a [Promise](https://github.com/reactphp/promise)-based
interface that makes it easy to react to when an action is completed
(i.e. either successfully fulfilled or rejected with an error):

```php
$sender->ping()->then(
    function (Clue\React\Ami\Protocol\Response $response) {
        // response received for ping action
    },
    function (Exception $e) {
        // an error occured while executing the action
        
        if ($e instanceof Clue\React\Ami\Protocol\ErrorException) {
            // we received a valid error response (such as authorization error)
            $response = $e->getResponse();
        } else {
            // we did not receive a valid response (likely a transport issue)
        }
    }
});
```

All actions resolve with a [`Response`](#response) object on success,
some actions are documented to return the specialized [`Collection`](#collection)
object to contain a list of entries.

#### Blocking

As stated above, this library provides you a powerful, async API by default.

If, however, you want to integrate this into your traditional, blocking environment,
you should look into also using [clue/reactphp-block](https://github.com/clue/reactphp-block).

The resulting blocking code could look something like this:

```php
use Clue\React\Block;
use React\EventLoop\Loop;

function getSipPeers()
{
    $factory = new Clue\React\Ami\Factory();

    $target = 'name:password@localhost';
    $promise = $factory->createClient($target)->then(function (Clue\React\Ami\Client $client) {
        $sender = new Clue\React\Ami\ActionSender($client);
        $ret = $sender->sipPeers()->then(function (Clue\React\Ami\Collection $collection) {
            return $collection->getEntryEvents();
        });
        $client->end();
        return $ret;
    });

    return Block\await($promise, Loop::get(), 5.0);
}
```

Refer to [clue/reactphp-block](https://github.com/clue/reactphp-block#readme) for more details.

### Message

The `Message` is an abstract base class for the [`Response`](#response),
[`Action`](#action) and [`Event`](#event) value objects.
It provides a common interface for these three message types.

Each `Message` consists of any number of fields with each having a name and one or multiple values.
Field names are matched case-insensitive. The interpretation of values is application-specific.

#### getFieldValue()

The `getFieldValue(string $key): ?string` method can be used to
get the first value for the given field key.

If no value was found, `null` is returned.

#### getFieldValues()

The `getFieldValues(string $key): string[]` method can be used to
get a list of all values for the given field key.

If no value was found, an empty `array()` is returned.

#### getFieldVariables()

The `getFieldVariables(string $key): array` method can be used to
get a hashmap of all variable assignments in the given $key.

If no value was found, an empty `array()` is returned.

#### getFields()

The `getFields(): array` method can be used to
get an array of all fields.

#### getActionId()

The `getActionId(): string` method can be used to
get the unique action ID of this message.

This is a shortcut to get the value of the "ActionID" field.

### Response

The `Response` value object represents the incoming response received from the AMI.
It shares all properties of the [`Message`](#message) parent class.

#### getCommandOutput()

The `getCommandOutput(): ?string` method can be used to get the resulting output of
a "command" [`Action`](#action).
This value is only available if this is actually a response to a "command" action,
otherwise it defaults to `null`.

```php
$sender->command('help')->then(function (Clue\React\Ami\Protocol\Response $response) {
    echo $response->getCommandOutput();
});
```

### Collection

The `Collection` value object represents an incoming response received from the AMI
for certain actions that return a list of entries.
It shares all properties of the [`Response`](#response) parent class.

You can access the `Collection` like a normal `Response` in order to access
the leading `Response` for this collection or you can use the below methods
to access the list entries and completion event.

```
Action: CoreShowChannels

Response: Success
EventList: start
Message: Channels will follow

Event: CoreShowChannel
Channel: SIP / 123
ChannelState: 6
ChannelStateDesc: Up
…

Event: CoreShowChannel
Channel: SIP / 456
ChannelState: 6
ChannelStateDesc: Up
…

Event: CoreShowChannel
Channel: SIP / 789
ChannelState: 6
ChannelStateDesc: Up
…

Event: CoreShowChannelsComplete
EventList: Complete
ListItems: 3
```

#### getEntryEvents()

The `getEntryEvents(): Event[]` method can be used to
get the list of all intermediary `Event` objects where each entry represents a single entry in the collection.

```php
foreach ($collection->getEntryEvents() as $entry) {
    assert($entry instanceof Clue\React\Ami\Protocol\Event);
    echo $entry->getFieldValue('Channel') . PHP_EOL;
}
```

#### getCompleteEvent()

The `getCompleteEvent(): Event` method can be used to
get the trailing `Event` that completes this collection.

```php
echo $collection->getCompleteEvent()->getFieldValue('ListItems') . PHP_EOL;
```

### Action

The `Action` value object represents an outgoing action message to be sent to the AMI.
It shares all properties of the [`Message`](#message) parent class.

#### getMessageSerialized()

The `getMessageSerialized(): string` method can be used to
get the serialized version of this outgoing action to send to Asterisk.

This method is considered advanced usage and mostly used internally only.

### Event

The `Event` value object represents the incoming event received from the AMI.
It shares all properties of the [`Message`](#message) parent class.

#### getName()

The `getName(): ?string` method can be used to get the name of the event.

This is a shortcut to get the value of the "Event" field.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require clue/ami-react:^1.1
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+.
It's *highly recommended to use PHP 7+* for this project.

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

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
