<?php

namespace Lenius\LaravelEcommerce\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lenius\Basket\Item;
use Lenius\Basket\ItemInterface;
use Lenius\LaravelEcommerce\Facades\Cart;

class EcommerceController extends Controller
{
    public static function routes(): void
    {
        $router = app()->make('router');

        $router->get('cart/show', [self::class, 'index'])->name('ecommerce.cart.show');
        $router->get('cart/debug', [self::class, 'debug'])->name('ecommerce.cart.debug');
        $router->get('cart/demo', [self::class, 'demo'])->name('ecommerce.cart.demo');
        $router->get('cart/destroy', [self::class, 'destroy'])->name('ecommerce.cart.destroy');
        $router->post('cart/update', [self::class, 'update'])->name('ecommerce.cart.update');

        $router->any('cart/{id}/add', [self::class, 'add'])->name('ecommerce.cart.item.add');
        $router->get('cart/{id}/dec', [self::class, 'dec'])->name('ecommerce.cart.item.dec');
        $router->get('cart/{id}/inc', [self::class, 'inc'])->name('ecommerce.cart.item.inc');
        $router->get('cart/{id}/remove', [self::class, 'remove'])->name('ecommerce.cart.item.remove');
    }

    public function index(): Factory|View|Application
    {
        return view('ecommerce::cart');
    }

    public function add(Request $request, string $id): RedirectResponse
    {
        $item = [
            'id'            => 1,
            'number'        => 'zxy',
            'name'          => 'My product',
            'stock'         => 'In stock',
            'unit'          => 'M',
            'tax'           => 25,
            'price'         => 100,
            'weight'        => 100,
            'quantity'      => 1,
            'type'          => 'item',
            'link'          => '',
        ];

        Cart::insert(new Item($item));

        return redirect()->route('ecommerce.cart.show');
    }

    public function debug(): array
    {
        return [
            'items'        => Cart::contents(),
            'sum'          => Cart::total(false),
            'tax'          => Cart::tax(),
            'total'        => Cart::total(),
            'weight'       => Cart::weight(),
            'total_items'  => Cart::totalItems(),
        ];
    }

    public function destroy(): RedirectResponse
    {
        Cart::destroy();

        return redirect()->route('ecommerce.cart.show');
    }

    public function update(Request $request): RedirectResponse
    {
        $items = $request->input('quantity');

        if ($items) {
            /* @var ItemInterface $item */
            foreach ($items as $itemIdentifier => $quantity) {
                if ($item = Cart::item($itemIdentifier)) {
                    if ($quantity > 0) {
                        $item->quantity = (int) $quantity;
                    } else {
                        Cart::remove($itemIdentifier);
                    }
                }
            }
        }

        return redirect()->route('ecommerce.cart.show');
    }

    public function inc(string $itemIdentifier): RedirectResponse
    {
        /** @var ItemInterface $item */
        if ($item = Cart::item($itemIdentifier)) {
            if ($item->quantity > 0) {
                ++$item->quantity;
            } else {
                Cart::remove($itemIdentifier);
            }
        }

        return redirect()->route('ecommerce.cart.show');
    }

    public function dec(string $itemIdentifier): RedirectResponse
    {
        /** @var ItemInterface $item */
        if ($item = Cart::item($itemIdentifier)) {
            if ($item->quantity > 1) {
                --$item->quantity;
            } else {
                Cart::remove($itemIdentifier);
            }
        }

        return redirect()->route('ecommerce.cart.show');
    }

    public function remove(string $itemIdentifier): RedirectResponse
    {
        if (Cart::item($itemIdentifier)) {
            Cart::remove($itemIdentifier);
        }

        return redirect()->route('ecommerce.cart.show');
    }

    public function demo(): RedirectResponse
    {
        $item = [
            'id'            => 1,
            'number'        => 'zxy',
            'name'          => 'My product',
            'stock'         => 'In stock',
            'unit'          => 'M',
            'tax'           => 25,
            'price'         => 100,
            'weight'        => 100,
            'quantity'      => 1,
            'type'          => 'item',
            'link'          => '',
        ];

        Cart::insert(new Item($item));

        return redirect()->route('ecommerce.cart.show');
    }
}
