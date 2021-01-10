<?php

namespace Lenius\LaravelEcommerce\Controllers;

use Lenius\Basket\ItemInterface;
use Lenius\LaravelEcommerce\Facades\Basket;

class EcommerceController extends Controller
{
    public static function routes()
    {
        $router = app()->make('router');

        $router->get('basket', [EcommerceController::class, 'index'])->name('basket');
        $router->get('basket/debug', [EcommerceController::class, 'debug'])->name('basket.debug');
        $router->get('basket/destroy', [EcommerceController::class, 'destroy'])->name('basket.destroy');
        $router->any('basket/{id}/add', [EcommerceController::class, 'add'])->name('basket.add');
        $router->get('basket/{id}/dec', [EcommerceController::class, 'dec'])->name('basket.dec');
        $router->get('basket/{id}/inc', [EcommerceController::class, 'inc'])->name('basket.inc');
        $router->get('basket/{id}/remove', [EcommerceController::class, 'remove'])->name('basket.remove');
    }

    public function index()
    {
        return Basket::contents();
    }

    public function debug()
    {
        dd(Basket::contents());
    }

    public function destroy()
    {
        Basket::destroy();

        return Basket::contents();
    }

    public function inc($itemIdentifier)
    {
        /** @var ItemInterface $item */
        if ($item = Basket::item($itemIdentifier)) {
            if ($item->quantity > 0) {
                ++$item->quantity;
            } else {
                Basket::remove($itemIdentifier);
            }
        }

        return Basket::contents();
    }

    public function dec($itemIdentifier)
    {
        /** @var ItemInterface $item */
        if ($item = Basket::item($itemIdentifier)) {
            if ($item->quantity > 1) {
                --$item->quantity;
            } else {
                Basket::remove($itemIdentifier);
            }
        }

        return Basket::contents();
    }

    public function remove($itemIdentifier)
    {
        if ($item = Basket::item($itemIdentifier)) {
            Basket::remove($itemIdentifier);
        }

        return Basket::contents();
    }
}
