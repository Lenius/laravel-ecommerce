
# Ecommerce for Laravels App

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lenius/laravel-ecommerce.svg?style=flat-square)](https://packagist.org/packages/lenius/laravel-ecommerce)
[![tests](https://github.com/Lenius/laravel-ecommerce/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Lenius/laravel-ecommerce/actions/workflows/tests.yml)
[![Code Coverage](https://scrutinizer-ci.com/g/Lenius/laravel-ecommerce/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/Lenius/laravel-ecommerce/?branch=main)
[![Total Downloads](https://poser.pugx.org/lenius/laravel-ecommerce/downloads.svg)](https://packagist.org/packages/laravel-ecommerce)
[![License](https://poser.pugx.org/lenius/laravel-ecommerce/license.svg)](https://packagist.org/packages/Lenius/laravel-ecommerce)

## Installation

You can install this package via composer using:

```bash
composer require lenius/laravel-ecommerce
```

You can then export the configuration:

```bash
php artisan vendor:publish --provider="Lenius\LaravelEcommerce\EcommerceServiceProvider" --tag="config"
php artisan vendor:publish --provider="Lenius\LaravelEcommerce\EcommerceServiceProvider" --tag="lang"
php artisan vendor:publish --provider="Lenius\LaravelEcommerce\EcommerceServiceProvider" --tag="views"
```
## Testing

Run the tests with:

``` bash
composer psalm
composer stan
composer test
composer test-coverage
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email info@lenius.dk
instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
