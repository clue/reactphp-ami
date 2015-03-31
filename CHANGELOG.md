# CHANGELOG

This file is a manually maintained list of changes for each release. Feel free
to add your changes here when sending pull requests. Also send corrections if
you spot any mistakes.

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
