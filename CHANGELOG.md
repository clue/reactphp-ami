# Changelog

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
