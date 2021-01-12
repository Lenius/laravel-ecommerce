<?php

namespace Lenius\LaravelEcommerce\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lenius\Basket\Item;
use Lenius\Basket\ItemInterface;
use Lenius\LaravelEcommerce\Facades\Basket;

class EcommerceController extends Controller
{
    public static function routes(): void
    {
        $router = app()->make('router');

        $router->get('basket', [EcommerceController::class, 'index'])->name('ecommerce.basket');
        $router->get('basket/debug', [EcommerceController::class, 'debug'])->name('ecommerce.basket.debug');
        $router->get('basket/demo', [EcommerceController::class, 'demo'])->name('ecommerce.basket.demo');
        $router->get('basket/destroy', [EcommerceController::class, 'destroy'])->name('ecommerce.basket.destroy');
        $router->post('basket/update', [EcommerceController::class, 'update'])->name('ecommerce.basket.update');

        $router->any('basket/{id}/add', [EcommerceController::class, 'add'])->name('ecommerce.basket.item.add');
        $router->get('basket/{id}/dec', [EcommerceController::class, 'dec'])->name('ecommerce.basket.item.dec');
        $router->get('basket/{id}/inc', [EcommerceController::class, 'inc'])->name('ecommerce.basket.item.inc');
        $router->get('basket/{id}/remove', [EcommerceController::class, 'remove'])->name('ecommerce.basket.item.remove');
    }

    public function index(): View
    {
        return view('ecommerce::basket');
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

        Basket::insert(new Item($item));

        return redirect()->route('ecommerce.basket');
    }

    public function debug(): array
    {
        return [
            'items'        => Basket::contents(),
            'sum'          => Basket::total(false),
            'tax'          => Basket::tax(),
            'total'        => Basket::total(),
            'weight'       => Basket::weight(),
            'total_items'  => Basket::totalItems(),
        ];
    }

    public function destroy(): RedirectResponse
    {
        Basket::destroy();

        return redirect()->route('ecommerce.basket');
    }

    public function update(Request $request): RedirectResponse
    {
        $items = $request->input('quantity');

        if ($items) {
            /* @var ItemInterface $item */
            foreach ($items as $itemIdentifier => $quantity) {
                if ($item = Basket::item($itemIdentifier)) {
                    if ($quantity > 0) {
                        $item->quantity = (int) $quantity;
                    } else {
                        Basket::remove($itemIdentifier);
                    }
                }
            }
        }

        return redirect()->route('ecommerce.basket');
    }

    public function inc(string $itemIdentifier): RedirectResponse
    {
        /** @var ItemInterface $item */
        if ($item = Basket::item($itemIdentifier)) {
            if ($item->quantity > 0) {
                ++$item->quantity;
            } else {
                Basket::remove($itemIdentifier);
            }
        }

        return redirect()->route('ecommerce.basket');
    }

    public function dec(string $itemIdentifier): RedirectResponse
    {
        /** @var ItemInterface $item */
        if ($item = Basket::item($itemIdentifier)) {
            if ($item->quantity > 1) {
                --$item->quantity;
            } else {
                Basket::remove($itemIdentifier);
            }
        }

        return redirect()->route('ecommerce.basket');
    }

    public function remove(string $itemIdentifier): RedirectResponse
    {
        if (Basket::item($itemIdentifier)) {
            Basket::remove($itemIdentifier);
        }

        return redirect()->route('ecommerce.basket');
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

        Basket::insert(new Item($item));

        return redirect()->route('ecommerce.basket');
    }
}
