<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'data' => null, 'message' => 'Unauthenticated.'], 401);
        }
        if (!in_array($user->type, $roles)) {
            return response()->json(['success' => false, 'data' => null, 'message' => 'Forbidden. Insufficient permissions.'], 403);
        }
        return $next($request);
    }
}
