<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Configure trusted proxies
        // Option 1: Use environment variable TRUSTED_PROXIES (comma-separated list)
        // Example: TRUSTED_PROXIES="192.168.1.0/24,10.0.0.0/8"
        // Option 2: For load balancers like AWS ELB, use TRUSTED_PROXIES="*" with specific headers
        // Option 3: Leave empty array to not trust any proxies (most secure for direct connections)
        
        $trustedProxies = env('TRUSTED_PROXIES', '');
        
        if ($trustedProxies === '*') {
            // Only use '*' when behind a trusted load balancer (AWS ELB, etc.)
            // and ALWAYS specify which headers to trust
            $middleware->trustProxies(
                at: '*',
                headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                        \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                        \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                        \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                        \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
            );
        } elseif (!empty($trustedProxies)) {
            // Trust specific proxy IPs/ranges from environment variable
            $proxies = array_map('trim', explode(',', $trustedProxies));
            $middleware->trustProxies(at: $proxies);
        }
        // If TRUSTED_PROXIES is not set or empty, no proxies will be trusted (default secure behavior)
        $middleware->validateCsrfTokens(except: [
            // Remove general login exceptions for security
        ]);
        $middleware->append(\App\Http\Middleware\ContentSecurityPolicy::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
