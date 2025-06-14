# Changes in PHPCPD

All notable changes in PHPCPD are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [8.2.3] - 2025-05-25

### Updated

* Improved html report and DataTables buttons, updated bootstrap and DataTables to the latest version.

## [8.2.2] - 2025-05-18

### Updated

* Improved progress bar.

## [8.2.1] - 2025-05-03

### Updated

* Fixed codefragment value for xml report.

## [8.2.0] - 2025-05-01

### Added

* Added possibility to use [xalan](https://xalan.apache.org) cli tool for generating html report with datatable and possibility to export into csv, excel, pdf formats or print it.

### Updated

* Changed and extended xml report format.

## [8.1.1] - 2025-04-13

### Added

* Tool can be installed using composer, updated composer dependencies.

## [8.1.0] - 2025-03-23

### Added

* Added Suffix Tree-based algorithm for code clone detection (experimental), added progress bar.

### Updated

* Made codebase refactoring. Updated packages: sebastian/cli-parser, sebastian/version, phpunit/php-file-iterator, phpunit/php-timer. Updated tests to the PHPUnit 12.

## [8.0.0] - 2024-12-30

### Updated

* Made codebase refactoring. Updated requirements php 8.3, updated composer dependencies, updated tests to the PHPUnit 11. Updated dev environment to the php 8.4, Phing 3.0, added code quality tools: ecs, phpstan.

## [7.0.1] - 2024-01-12

### Added

* Skip php attributes from analysis (including multilines attributes, see example inside tests/fixture/Math.php)

## [7.0.0] - 2022-MM-DD

### Added

* [#199](https://github.com/sebastianbergmann/phpcpd/pull/199): Suffix Tree-based algorithm for code clone detection

### Removed

* Removed support for PHP versions older than PHP 8.1

## [6.0.3] - 2020-12-07

### Changed

* Changed PHP version constraint in `composer.json` from `^7.3` to `>=7.3`

## [6.0.2] - 2020-08-18

### Fixed

* [#187](https://github.com/sebastianbergmann/phpcpd/issues/187): Exclude arguments are being handled as prefixes

## [6.0.1] - 2020-08-13

### Fixed

* The `--verbose` CLI option had no effect

## [6.0.0] - 2020-08-13

### Removed

* The `--names` CLI option has been removed; use the `--suffix` CLI option instead
* The `--names-exclude` CLI option has been removed; use the `--exclude` CLI option instead
* The `--regexps-exclude` CLI option has been removed
* The `--progress` CLI option has been removed

## [5.0.2] - 2020-02-22

### Changed

* Require `sebastian/version` version 3 and `phpunit/php-timer` version 3 to allow Composer-based installation alongside `phploc/phploc` version 6 and `phpunit/phpunit` version 9 

## [5.0.1] - 2020-02-20

### Fixed

* [#181](https://github.com/sebastianbergmann/phpcpd/issues/181): `--min-lines`, `--min-tokens`, and `--fuzzy` commandline options do not work

## [5.0.0] - 2020-02-20

### Removed

* Removed support for PHP versions older than PHP 7.3

## [4.1.0] - 2018-09-17

### Added

* Implemented [#117](https://github.com/sebastianbergmann/phpcpd/issues/117): Report average and maximum length of code clone

### Changed

* The text logger now prints code clones sorted by size (in descending order)

## [4.0.0] - 2018-01-02

### Removed

* Removed support for PHP versions older than PHP 7.1

## [3.0.1] - 2017-11-16

### Fixed

* [#147](https://github.com/sebastianbergmann/phpcpd/issues/147): Wrong exit code when no files were found to be scanned
* [#152](https://github.com/sebastianbergmann/phpcpd/issues/152): Version requirement for `sebastian/version` is too strict

## [3.0.0] - 2017-02-05

### Added

* [#90](https://github.com/sebastianbergmann/phpcpd/pull/90): The PMD logger now replaces all characters that are invalid XML with `U+FFFD`
* [#100](https://github.com/sebastianbergmann/phpcpd/pull/100): Added the `--regexps-exclude` option

### Changed

* When the Xdebug extension is loaded, PHPCPD disables as much of Xdebug's functionality as possible to minimize the performance impact

### Removed

* Removed support for PHP versions older than PHP 5.6

[7.0.1]: https://github.com/systemsdk/phpcpd
[7.0.0]: https://github.com/sebastianbergmann/phpcpd/compare/6.0.3...master
[6.0.3]: https://github.com/sebastianbergmann/phpcpd/compare/6.0.2...6.0.3
[6.0.2]: https://github.com/sebastianbergmann/phpcpd/compare/6.0.1...6.0.2
[6.0.1]: https://github.com/sebastianbergmann/phpcpd/compare/6.0.0...6.0.1
[6.0.0]: https://github.com/sebastianbergmann/phpcpd/compare/5.0.2...6.0.0
[5.0.2]: https://github.com/sebastianbergmann/phpcpd/compare/5.0.1...5.0.2
[5.0.1]: https://github.com/sebastianbergmann/phpcpd/compare/5.0.0...5.0.1
[5.0.0]: https://github.com/sebastianbergmann/phpcpd/compare/4.1.0...5.0.0
[4.1.0]: https://github.com/sebastianbergmann/phpcpd/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/sebastianbergmann/phpcpd/compare/3.0.1...4.0.0
[3.0.1]: https://github.com/sebastianbergmann/phpcpd/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/sebastianbergmann/phpcpd/compare/2.0...3.0.0

