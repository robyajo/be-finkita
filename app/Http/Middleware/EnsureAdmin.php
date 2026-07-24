<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'ADMIN') {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'code' => 403,
            'message' => 'Forbidden: Admin access required',
        ], 403);
    }
}
