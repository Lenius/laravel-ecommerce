
# Ecommerce for Laravels App

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lenius/laravel-ecommerce.svg?style=flat-square)](https://packagist.org/packages/lenius/laravel-ecommerce)
[![tests](https://github.com/Lenius/laravel-ecommerce/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Lenius/laravel-ecommerce/actions/workflows/tests.yml)
[![Code Coverage](https://scrutinizer-ci.com/g/Lenius/laravel-ecommerce/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/Lenius/laravel-ecommerce/?branch=main)
[![Total Downloads](https://poser.pugx.org/lenius/laravel-ecommerce/downloads.svg)](https://packagist.org/packages/laravel-ecommerce)
[![License](https://poser.pugx.org/lenius/laravel-ecommerce/license.svg)](https://packagist.org/packages/Lenius/laravel-ecommerce)

## Installation

This package supports Laravel 11 through 13 and PHP 8.3 through 8.5.

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

## Overview
Look at one of the following topics to learn more

* [Usage](#usage)
* [Events](#events)

## Usage

The shoppingcart gives you the following methods to use:

### Cart::insert()

Adding an item to the cart is really simple, you just use the `insert()` method, which accepts a variety of parameters.

In its most basic form you can specify the id, name, quantity, price of the product you'd like to add to the cart.

```php
Cart::insert(new Item([
    'id'       => 'foo',
    'name'     => 'bar',
    'price'    => 100,
    'quantity' => 2,
    'weight'   => 300
]));
```

Cart::insert() accept a class which implements ItemInterface
```php
class CustomItem implements ItemInterface
{

}
```

### Inserting items with options into the cart
Inserting an item into the cart is easy. The required keys are id, name, price and quantity, although you can pass
over any custom data that you like. If option items contains price or weight there values are added to the total weight / price of the product.

```php
Cart::insert(new Item([
    'id'         => 'foo',
    'name'       => 'bar',
    'price'      => 100,
    'quantity'   => 2,
    'weight'     => 300,
    'options'    => [
       [
        'name'   => 'Size',
        'value'  => 'L',
        'weight' => 50,
        'price'  => 10
       ],
     ],
]));
```

### Setting the tax rate for an item
Another key you can pass to your insert method is tax'. This is a percentage which you would like to be added onto
the price of the item.

In the below example we will use 25% for the tax rate.

```php
Cart::insert(new Item([
    'id'       => 'mouseid',
    'name'     => 'Mouse',
    'price'    => 100,
    'quantity' => 1,
    'tax'      => 25,
    'weight'   => 200
]));
```

### Updating items in the cart
You can update items in your cart by updating any property on a cart item. For example, if you were within a
cart loop then you can update a specific item using the below example.
```php
foreach (Cart::contents() as $item) {
    $item->name = 'Foo';
    $item->setQuantity(1);
}
```

### Destroying/emptying the cart
You can completely empty/destroy the cart by using the ```destroy()``` method.
```php
Cart::destroy()
```

### Retrieve the cart contents
You can loop the cart contents by using the following method
```php
Cart::contents();
```

You can also return the Cart items as an array by passing true as the first argument
```php
Cart::contents(true);
```

### Check if the Cart has an item
```php
Cart::has($itemIdentifier);
```

### Remove an item from the Cart
```php
Cart::remove($itemIdentifier)
```

### Increment an item from the Cart
```php
Cart::inc($itemIdentifier)
```

### Decrement an item from the Cart
```php
Cart::dec($itemIdentifier)
```

## Events

The cart also has events build in. There are five events available for you to listen for.

| Event               | Fired                                  | Parameter                            |
|---------------------|----------------------------------------|--------------------------------------|
| CartItemUpdated     | When an item in the cart was updated.  | The `CartItem` that was updated.     |
| CartItemRemoved     | When an item is removed from the cart. | The `CartItem` that was removed.     |
| CartItemDecreased   | When an item is dec from the cart.     | The `CartItem` that was decreased.   |
| CartItemIncremented | When an item is inc from the cart.     | The `CartItem` that was incremented. |
| CartDestroyed       | When the cart was destroyed.           | -                                    |

## Containerized development

The `./app` helper runs the development tools in a single PHP CLI container.
It does not start a web server, database, or other services. The image contains
PHP, Composer, Git, unzip, and the PHP extensions required by Laravel, PHPUnit,
and the package dependencies.

Docker with Linux container support is required. Use `./app` from Bash or
`app.ps1` directly from Windows PowerShell. Make the Bash helper executable
after cloning if necessary:

```bash
chmod +x app
```

### Windows quick start

Install and start Docker Desktop and make sure it is using Linux containers.
Then open PowerShell in the project directory. On a fresh clone, build the test
container, install the Composer dependencies, and run all checks:

```powershell
Set-Location C:\path\to\laravel-ecommerce
.\app.ps1 build
.\app.ps1 composer-install
.\app.ps1 check
```

The build creates the local image `laravel-ecommerce-dev:php-8.5`. The
`check` command runs strict Composer validation, PHPUnit, and PHPStan. Source
files and the `vendor` directory are shared between Windows and the container
through the project mount.

An explicit build is optional after the initial dependency installation. If an
image is missing, commands such as `.\app.ps1 check` build it automatically.

To rebuild the image from the latest base image without using Docker's build
cache, run:

```powershell
.\app.ps1 build --force
```

Build and test with another supported PHP version by placing `--php` before the
command. Every PHP version uses a separate Docker image:

```powershell
.\app.ps1 --php 8.4 build
.\app.ps1 --php 8.4 composer-update
.\app.ps1 --php 8.4 check
```

PHP 8.5 is the default, so `--php 8.5` can normally be omitted. Run
`composer-update` after changing PHP versions if the existing dependencies were
resolved on a different PHP version.

If the Windows execution policy blocks local scripts, either allow scripts for
the current PowerShell process:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\app.ps1 check
```

or start the script with a process-scoped bypass without changing the machine
policy:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\app.ps1 check
```

### Bash quick start

On Linux, macOS, or in WSL, the equivalent fresh-clone workflow is:

```bash
./app build
./app composer-install
./app check
```

The default image is built automatically when it is missing. Force a clean
rebuild with:

```bash
./app build --force
```

Use PHP 8.4 explicitly with:

```bash
./app --php 8.4 build
./app --php 8.4 composer-update
./app --php 8.4 check
```

The project is mounted at `/app`, so source changes and Composer changes are
written directly to the working tree. Composer's download cache is persisted
in `~/.cache/composer` on the host.

### Available commands

| Command | Description |
|---|---|
| `./app build [--force]` | Build or rebuild the PHP tool image. |
| `./app check` | Run Composer validation, PHPUnit, and PHPStan. |
| `./app test [arguments]` | Run PHPUnit and forward optional arguments. |
| `./app stan [arguments]` | Run PHPStan and forward optional arguments. |
| `./app php-cs-fixer [arguments]` | Run PHP CS Fixer and apply fixes. |
| `./app composer-install [arguments]` | Install Composer dependencies. |
| `./app composer-update [arguments]` | Update Composer dependencies. |
| `./app composer-validate [arguments]` | Validate `composer.json` strictly. |
| `./app composer-outdated [arguments]` | List outdated Composer dependencies. |
| `./app composer [arguments]` | Run any Composer command. |
| `./app php [arguments]` | Run any PHP command. |
| `./app php-shell` | Open an interactive Bash shell in the PHP container. |
| `./app help` | Display the command overview. |

Arguments are forwarded to the underlying tool. For example:

```bash
./app test --filter CartTest
./app php -v
./app composer show --direct
./app composer check-platform-reqs
```

The PowerShell helper accepts the same commands and arguments. Replace `./app`
with `.\app.ps1`:

```powershell
.\app.ps1 test --filter CartTest
.\app.ps1 php -v
.\app.ps1 composer check-platform-reqs
```

Select another supported PHP version with `--php`. Each version gets its own
local Docker image:

```bash
./app --php 8.4 build
./app --php 8.4 composer-update
./app --php 8.4 test
```

The equivalent PowerShell syntax is `.\app.ps1 --php 8.4 test`.

### Running without Docker

The equivalent local Composer commands are:

```bash
composer validate --strict
composer test
composer analyse
composer fix
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email info@lenius.dk
instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
