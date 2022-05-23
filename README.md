
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

## Overview
Look at one of the following topics to learn more

* [Usage](#usage)
* [Events](#events)

## Usage

The shoppingcart gives you the following methods to use:

### Cart::add()

Adding an item to the cart is really simple, you just use the `add()` method, which accepts a variety of parameters.

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
    $item->quantity = 1;
}
```

### Destroying/emptying the cart
You can completely empty/destroy the basket by using the ```destroy()``` method.
```php
Cart::destroy()
```

### Retrieve the cart contents
You can loop the basket contents by using the following method
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
Cart::remove($identifier)
```

## Events

The cart also has events build in. There are five events available for you to listen for.

| Event          | Fired                                  | Parameter                        |
|----------------|----------------------------------------| -------------------------------- |
| cart.updated   | When an item in the cart was updated.  | The `CartItem` that was updated. |
| cart.removed   | When an item is removed from the cart. | The `CartItem` that was removed. |
| cart.destroyed | When the cart was destroyed.           | -                                |

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
