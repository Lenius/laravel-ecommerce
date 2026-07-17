<?php

namespace Lenius\LaravelEcommerce\Exceptions;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;

class CartConflictException extends RuntimeException implements Responsable
{
    public static function forIdentifier(string $identifier): self
    {
        return new self("The cart [{$identifier}] was changed by another request.");
    }

    public function toResponse($request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return response($this->getMessage(), 409);
    }
}
