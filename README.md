# Composer License Checker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dominikb/composer-license-checker.svg?style=flat-square)](https://packagist.org/packages/dominikb/composer-license-checker)
[![Build Status](https://img.shields.io/travis/dominikb/composer-license-checker/master.svg?style=flat-square)](https://travis-ci.com/dominikb/composer-license-checker)
[![Quality Score](https://img.shields.io/scrutinizer/g/dominikb/composer-license-checker.svg?style=flat-square)](https://scrutinizer-ci.com/g/dominikb/composer-license-checker)
[![Total Downloads](https://img.shields.io/packagist/dt/dominikb/composer-license-checker.svg?style=flat-square)](https://packagist.org/packages/dominikb/composer-license-checker)

Crawl through your dependencies and look up their licenses on [https://tldrlegal.com/](https://tldrlegal.com/) for a quick summary on whats allowed with each license and how the support may be used.

## Installation

You can install the package via composer:

```bash
composer require dominikb/composer-license-checker
```

## Usage

Two separate commands are provided:
* `./composer-license-checker check`
* `./composer-license-checker report`

Use `./composer-license-checker help` to get info about general usage or use the syntax `./composer-license-checker help COMMAND_NAME` to see more information about a specific command available. 

> This package is in an early phase. The API may still change before a first release.

``` bash
vendor/bin/composer-license-checker check --whitelist MIT

vendor/bin/composer-license-checker report -p /path/to/your/project -c /path/to/composer.phar
```

### Testing

``` bash
composer test
```

Code coverage reports are output to the `build` folder. See `.phpunit.xml.dist` for more testing configuration.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email bauernfeind.dominik@gmail.com instead of using the issue tracker.

## Credits

- [Dominik Bauernfeind](https://github.com/dominikb)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
