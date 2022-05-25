<?php

namespace Lenius\LaravelEcommerce\Identifier;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Lenius\Basket\IdentifierInterface;

/**
 * Class LaravelCookie.
 */
class LaravelCookie implements IdentifierInterface
{
    /**
     * Get the current or new unique identifier.
     *
     * @return string The identifier
     */
    public function get(): string
    {
        if (request()?->hasCookie('cart_identifier')) {
            $cookie = request()?->cookie('cart_identifier');

            if (is_string($cookie) && ! empty($cookie)) {
                return $cookie;
            }
        }

        return $this->regenerate();
    }

    /**
     * Regenerate the identifier.
     *
     * @return string The identifier
     */
    public function regenerate(): string
    {
        $identifier = (string) Str::uuid();

        Cookie::queue(cookie('cart_identifier', $identifier, 0, '/'));

        return $identifier;
    }

    /**
     * Forget the identifier.
     *
     * @return void
     */
    public function forget(): void
    {
        Cookie::queue(Cookie::forget('cart_identifier'));
    }
}
