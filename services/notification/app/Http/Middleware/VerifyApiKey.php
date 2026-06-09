<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('api.key');

        if ($expectedKey === null || $expectedKey === '') {
            return response()->json(['message' => 'API key is not configured.'], 500);
        }

        $providedKey = $request->header('X-Api-Key');

        if ($providedKey === null || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
