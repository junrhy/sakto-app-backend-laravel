<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user is active
        if (!$request->user()->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is deactivated'
            ], 403);
        }

        // Update last seen timestamp
        $request->user()->update(['last_seen_at' => now()]);

        return $next($request);
    }
}
