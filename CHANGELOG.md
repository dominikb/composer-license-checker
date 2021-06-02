# Changelog

All notable changes to `composer-license-checker` will be documented in this file

## 2.2.0 - TBD

### Added
- __--allow__ option to always allow a specific package or author/vendor

## 2.1.0 - 2020-12-31

### Added
- Support for PHP 8

### Changed
- Upgrade PHPUnit major version from `7` to `9`
- Upgraded several dependencies to work with newer PHP versions. Major version bump includes [symfony/dom-crawler](https://github.com/symfony/dom-crawler).

### Removed
- Dropped support for PHP 7.1 and 7.2 [(see: supported versions)](https://www.php.net/supported-versions.php)

## 2.0.0 - 2020-06-17

### Changed
- Terminology for black/whitelist changed to block/allowlist (This changes the CLI interface of the `CheckCommand` )
- `Command` classes now return an integer exist code

## 1.0.1 - 2019-04-06

### Added
- Added Scrutinizer code coverage

### Changed
- Fixed path to autoloader when installed with composer

### Removed

## 1.0.0 - 2019-04-06

Initial Release

### Added
- `check` and `report` commands
- summary lookup on tldrlegal
- caching of lookups

### Changed

### Removed
