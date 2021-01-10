<?php

namespace Lenius\LaravelEcommerce\Test;

class FeatureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadRoutes();
    }

    public function test_ecommerce_basket_demo()
    {
        $this->get(route('ecommerce.basket'))
            ->assertStatus(200)
            ->assertSeeText('Basket empty');

        $this->get(route('ecommerce.basket.demo'))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.basket'));

        $this->get('/ecommerce/basket')
            ->assertStatus(200)
            ->assertSeeText('My product');

        $this->get(route('ecommerce.basket.debug'))
            ->assertJson([
                'sum'         => 100,
                'tax'         => 25,
                'total'       => 125,
                'total_items' => 1,
            ]);

        $this->get(route('ecommerce.basket.destroy'))
            ->assertStatus(302)
            ->assertRedirect(route('ecommerce.basket'));
    }
}
