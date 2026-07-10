<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySharedSecret
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.payment.secret');

        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing shared secret token.'
            ], 401);
        }

        return $next($request);
    }
}
