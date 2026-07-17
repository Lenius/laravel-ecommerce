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
        $name = $this->name();

        if (request()->hasCookie($name)) {
            $cookie = request()->cookie($name);

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

        Cookie::queue(cookie(
            $this->name(),
            $identifier,
            $this->minutes(),
            $this->path(),
        ));

        return $identifier;
    }

    /**
     * Forget the identifier.
     *
     * @return void
     */
    public function forget(): void
    {
        Cookie::queue(Cookie::forget($this->name(), $this->path()));
    }

    private function name(): string
    {
        $name = config('ecommerce.cookie.name', 'cart_identifier');

        return is_string($name) && $name !== '' ? $name : 'cart_identifier';
    }

    private function minutes(): int
    {
        $minutes = config('ecommerce.cookie.minutes', 43200);

        return is_numeric($minutes) ? (int) $minutes : 43200;
    }

    private function path(): string
    {
        $path = config('ecommerce.cookie.path', '/');

        return is_string($path) && $path !== '' ? $path : '/';
    }
}
