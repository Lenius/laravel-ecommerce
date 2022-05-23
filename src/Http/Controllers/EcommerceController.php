<?php

namespace Lenius\LaravelEcommerce\Http\Controllers;

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

        $router->get('basket', [EcommerceController::class, 'index'])->name('ecommerce.cart');
        $router->get('basket/debug', [EcommerceController::class, 'debug'])->name('ecommerce.cart.debug');
        $router->get('basket/demo', [EcommerceController::class, 'demo'])->name('ecommerce.cart.demo');
        $router->get('basket/destroy', [EcommerceController::class, 'destroy'])->name('ecommerce.cart.destroy');
        $router->post('basket/update', [EcommerceController::class, 'update'])->name('ecommerce.cart.update');

        $router->any('basket/{id}/add', [EcommerceController::class, 'add'])->name('ecommerce.cart.item.add');
        $router->get('basket/{id}/dec', [EcommerceController::class, 'dec'])->name('ecommerce.cart.item.dec');
        $router->get('basket/{id}/inc', [EcommerceController::class, 'inc'])->name('ecommerce.cart.item.inc');
        $router->get('basket/{id}/remove', [EcommerceController::class, 'remove'])->name('ecommerce.cart.item.remove');
    }

    public function index(): View
    {
        return view('ecommerce::cart');
    }

    public function add(Request $request, $id)
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

        return redirect()->route('ecommerce.cart');
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

        return redirect()->route('ecommerce.cart');
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

        return redirect()->route('ecommerce.cart');
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

        return redirect()->route('ecommerce.cart');
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

        return redirect()->route('ecommerce.cart');
    }

    public function remove(string $itemIdentifier): RedirectResponse
    {
        if (Cart::item($itemIdentifier)) {
            Cart::remove($itemIdentifier);
        }

        return redirect()->route('ecommerce.cart');
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

        return redirect()->route('ecommerce.cart');
    }
}
