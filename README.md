# Laravel client for Dinas Shipping API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dinas/shipping-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/dinas/shipping-sdk-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dinas/shipping-sdk-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/dinasjp/shipping-sdk-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/dinas/shipping-sdk-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/dinasjp/shipping-sdk-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/dinas/shipping-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/dinas/shipping-sdk-laravel)

Laravel SDK for the Dinas Shipping API.

It provides an expressive wrapper around the shipping REST endpoints, simplifying integration with Laravel applications.
Configuration is handled via environment variables, and the package offers typed resources and clean method calls for
all main API operations.

The package also supports webhook handling using spatie/laravel-webhook-client.
Incoming events are automatically verified, logged, and dispatched as Laravel events or jobs, enabling smooth
asynchronous updates such as shipment status changes or document availability.

## Installation

You can install the package via composer:

```bash
composer require dinas/shipping-sdk-laravel
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="shipping-sdk-laravel-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="shipping-sdk-laravel-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$shipping = new Dinas\Shipping();
echo $shipping->echoPhrase('Hello, Dinas!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dinas](https://github.com/dinasjp)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
