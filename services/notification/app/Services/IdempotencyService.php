<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    private const TTL_SECONDS = 86400;

    public function acquire(string $key): bool
    {
        return Cache::add($this->cacheKey($key), true, self::TTL_SECONDS);
    }

    public function release(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    public function storeResponse(string $key, array $response): void
    {
        Cache::put($this->responseKey($key), $response, self::TTL_SECONDS);
    }

    public function getStoredResponse(string $key): ?array
    {
        return Cache::get($this->responseKey($key));
    }

    private function cacheKey(string $key): string
    {
        return 'idempotency:lock:'.$key;
    }

    private function responseKey(string $key): string
    {
        return 'idempotency:response:'.$key;
    }
}
