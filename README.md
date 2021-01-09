
# Ecommerce for Laravels App

[![Build Status](https://travis-ci.org/Lenius/laravel-ecommerce.svg)](https://travis-ci.org/Lenius/laravel-ecommerce)
![tests](https://github.com/Lenius/laravel-ecommerce/workflows/tests/badge.svg?branch=main)
[![License](https://poser.pugx.org/Lenius/laravel-ecommerce/license.svg)](https://packagist.org/packages/Lenius/laravel-ecommerce)

## Installation

You can install this package via composer using:

```bash
composer require lenius/laravel-ecommerce
```

You can then create the table by running the
migrations:

```bash
php artisan migrate
```

php artisan vendor:publish --provider="Lenius\LaravelEcommerce\EcommerceServiceProvider" --tag="config"

## Testing

Run the tests with:

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email info@lenius.dk
instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
