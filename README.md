
# Ecommerce for Laravels App

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lenius/laravel-ecommerce.svg?style=flat-square)](https://packagist.org/packages/lenius/laravel-ecommerce)
[![Build Status](https://travis-ci.org/lenius/laravel-ecommerce.svg)](https://travis-ci.org/lenius/laravel-ecommerce)
![tests](https://github.com/lenius/laravel-ecommerce/workflows/tests/badge.svg?branch=main)
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
```

You can then create the tables by running the migrations:

```bash
php artisan migrate
```

## Testing

Run the tests with:

``` bash
composer test
composer test-coverage
composer psalm
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email info@lenius.dk
instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
