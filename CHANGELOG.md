# Changelog

## 1.2.0 (2024-02-23)

*   Feature: Forward compatibility with Promise v3.
    (#80 by @SimonFrings)

*   Feature: Simplify usage by supporting new [default loop](https://reactphp.org/event-loop/#loop) and new Socket API.
    (#70 by @clue and #71 by @SimonFrings)

    ```php
    // old (still supported)
    $factory = new Clue\React\Ami\Factory($loop);

    // new (using default loop)
    $factory = new Clue\React\Ami\Factory();
    ```

*   Feature: Full PHP 8.3 compatibility.
    (#67, #73 and #79 by @SimonFrings)

*   Minor documentation improvements.
    (#69 by @PaulRotmann and #77 by @yadaiio)

*   Improve test suite and use GitHub actions for continuous integration (CI).
    (#67 and #78 by @SimonFrings)

## 1.1.0 (2020-10-09)

*   Feature: Support authentication with URL-encoded special characters.
    (#66 by @clue)

    ```php
    $user = 'he:llo';
    $pass = 'p@ss';
    $promise = $factory->createClient(
        rawurlencode($user) . ':' . rawurlencode($pass) . '@localhost'
    );
    ```

*   Minor documentation improvements and add support / sponsorship info.
    (#58 by @clue)

*   Improve test suite and add `.gitattributes` to exclude dev files from exports.
    Prepare PHP 8 support, update to PHPUnit 9 and simplify test matrix.
    (#57 and #59 by @clue and #61 and #65 by @SimonFrings)

## 1.0.0 (2019-10-31)

*   **First stable release, now following SemVer!**

*   Feature / Fix: Support Asterisk 14+ command output format as well as legacy format.
    (#54 by @clue)

*   Feature / Fix: Support parsing messages with multiple newlines between messages.
    (#53 by @glet and @clue)

*   Improve README and API documentation.
    (#55 by @clue)

*   Improve test suite, support PHPUnit 7 - legacy PHPUnit 4, test against legacy PHP 5.3 through PHP 7.3
    and update project homepage.
    (#51 and #52 by @clue)

> Contains no other changes, so it's actually fully compatible with the v0.4.0 release.

## 0.4.0 (2017-09-04)

*   Feature / BC break: Simplify `Collection` by extending `Response` and merging `Collector` into `ActionSender`
    (#41 by @clue)

    ```php
    // old
    $collector = new Collector($client);
    $collector->coreShowChannels()->then(function (Collection $collection) {
        var_dump($collection->getResponse()->getFieldValue('Message'));
    });

    // new
    $collector = new ActionSender($client);
    $collector->coreShowChannels()->then(function (Collection $collection) {
        var_dump($collection->getFieldValue('Message'));
    });
    ```

*   Feature / BC break: Replace deprecated SocketClient with new Socket component and
    improve forward compatibility with upcoming ReactPHP components
    (#39 by @clue)

*   Feature / BC break: Consistently require URL when creating client
    (#40 by @clue)

## 0.3.2 (2017-09-04)

* Feature / Fix: Update SocketClient to v0.5 and fix secure connection via TLS
  (#38 by @clue)

* Improve test suite by adding PHPUnit to require-dev,
  fix HHVM build for now again and ignore future HHVM build errors, 
  test against legacy PHP 5.3 through PHP 7.1 and
  lock Travis distro so new defaults will not break the build
  (#34, #35, #36 and #37 by @clue)

## 0.3.1 (2016-11-01)

* Fix: Make parser more robust by supporting parsing messages with missing space after colon
  (#29 by @bonan, @clue)

* Improve documentation

## 0.3.0 (2015-03-31)

* BC break: Rename `Api` to `ActionSender` to reflect its responsibility
  ([#22](https://github.com/clue/php-ami-react/pull/22))

  * Rename invalid action method `logout()` to proper `logoff()`
    ([#17](https://github.com/clue/php-ami-react/issues/17))

* Feature: Add `Response::getCommandOutput()` helper
  ([#23](https://github.com/clue/php-ami-react/pull/23))

* Feature: Emit "error" event for unexpected response messages
  ([#21](https://github.com/clue/php-ami-react/pull/21))

* Functional integration test suite
  ([#18](https://github.com/clue/php-ami-react/pull/18) / [#24](https://github.com/clue/php-ami-react/pull/24))

## 0.2.0 (2014-07-20)

* Package renamed to "clue/ami-react"

## 0.1.0 (2014-07-17)

* First tagged release

## 0.0.0 (2014-06-25)

* Initial concept
