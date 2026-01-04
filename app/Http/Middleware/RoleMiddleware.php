<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        if (!$user || $user->role !== $role) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya petugas yang diizinkan.'
            ], 403);
        }

        return $next($request);
    }
}
