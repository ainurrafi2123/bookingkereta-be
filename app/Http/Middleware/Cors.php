<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigin = 'http://localhost:3000'; // domain frontend
        $allowedMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
        $allowedHeaders = 'Content-Type, Authorization, X-Requested-With, Accept';
        
        // Preflight request
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json([], 200, [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Methods' => $allowedMethods,
                'Access-Control-Allow-Headers' => $allowedHeaders,
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        $response = $next($request);

        // Tambahkan CORS headers ke response sebenarnya
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
        $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
