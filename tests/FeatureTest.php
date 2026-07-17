<?php

namespace Lenius\LaravelEcommerce\Test;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class FeatureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadRoutes();
    }

    public function test_database_migration_is_always_loaded(): void
    {
        $hasDatabaseCartTable = $this->app['db']
            ->connection()
            ->getSchemaBuilder()
            ->hasTable('ecommerce_carts');

        $this->assertTrue($hasDatabaseCartTable);
    }

    public function test_login_and_logout_rotate_the_cart_identifier_cookie(): void
    {
        // Establish an initial cart identifier cookie for the request.
        $this->get(route('ecommerce.cart.demo'));

        $user = User::query()->firstOrFail();

        event(new Login('web', $user, false));

        $this->assertTrue(
            $this->app['cookie']->hasQueued('cart_identifier'),
            'Expected the cart identifier cookie to be re-queued on login.',
        );
        $afterLogin = $this->app['cookie']->queued('cart_identifier')->getValue();
        $this->assertNotSame('', $afterLogin);

        event(new Logout('web', $user));

        $this->assertTrue(
            $this->app['cookie']->hasQueued('cart_identifier'),
            'Expected the cart identifier cookie to be cleared on logout.',
        );
        $afterLogout = $this->app['cookie']->queued('cart_identifier')->getValue();
        $this->assertNotSame($afterLogin, $afterLogout);
    }

    public function test_ecommerce_basket_add()
    {
        $this->get(route('ecommerce.cart.item.add', [1]))
            ->assertStatus(302);

        $this->get(route('ecommerce.cart.debug'))
            ->assertJson([
                'sum'         => 100,
                'tax'         => 25,
                'total'       => 125,
                'total_items' => 1,
                'weight'      => 100,
            ]);

    }

    public function test_ecommerce_basket_dec()
    {
        $this->get(route('ecommerce.cart.demo'))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.cart.show'));

        $basket = app('cart');

        $item = array_key_first($basket->contents());

        $this->get(route('ecommerce.cart.item.dec', [$item]))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.cart.show'));

        $this->get(route('ecommerce.cart.debug'))
            ->assertJson([
                'sum'         => 0,
                'tax'         => 0,
                'total'       => 0,
                'total_items' => 0,
                'weight'      => 0,
            ]);
    }

    public function test_ecommerce_basket_inc()
    {
        $this->get(route('ecommerce.cart.demo'))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.cart.show'));

        $basket = app('cart');

        $item = array_key_first($basket->contents());
        $this->get(route('ecommerce.cart.item.inc', [$item]))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.cart.show'));

        $this->get(route('ecommerce.cart.debug'))
            ->assertJson([
                'sum'         => 200,
                'tax'         => 50,
                'total'       => 250,
                'total_items' => 2,
                'weight'      => 200,
            ]);

    }

    public function test_ecommerce_basket_destroy()
    {
        $this->get(route('ecommerce.cart.demo'))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.cart.show'));

        $this->get(route('ecommerce.cart.destroy'))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.cart.show'));

        $this->get(route('ecommerce.cart.debug'))
            ->assertJson([
                'sum'         => 0,
                'tax'         => 0,
                'total'       => 0,
                'total_items' => 0,
                'weight'      => 0,
            ]);
    }
}
