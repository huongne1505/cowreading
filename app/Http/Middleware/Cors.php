<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('CORS Middleware: Handling request', [
            'method' => $request->getMethod(),
            'url' => $request->url(),
            'origin' => $request->header('Origin'),
        ]);
        
        if ($request->getMethod() === "OPTIONS") {
            \Log::info('CORS Middleware: Handling OPTIONS request');
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, Application');

        \Log::info('CORS Middleware: Added headers to response', [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
