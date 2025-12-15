<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            'http://localhost',
            'http://127.0.0.1',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'https://david-eya.github.io',
            'https://david-eya.github.io/MaximusHotel',
            'https://hotelmaximus.bytevortexz.com'
        ];
        
        $origin = $request->header('Origin');
        
        if (in_array($origin, $allowedOrigins) || 
            in_array(parse_url($origin, PHP_URL_HOST), ['localhost', '127.0.0.1'])) {
            $response = $next($request);
            
            return $response->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->header('Access-Control-Allow-Credentials', 'false')
                ->header('Access-Control-Max-Age', '86400');
        }
        
        $response = $next($request);
        
        return $response->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->header('Access-Control-Allow-Credentials', 'false')
            ->header('Access-Control-Max-Age', '86400');
    }
}


