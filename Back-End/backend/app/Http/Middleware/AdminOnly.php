<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role !== 'Admin') {
            return response()->json([
                'message' => 'Accès refusé — Admin uniquement'
            ], 403);
        }

        return $next($request);
    }
}
