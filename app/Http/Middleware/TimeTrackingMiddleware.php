<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\TimeCard;

class TimeTrackingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Automatic time tracking has been disabled
        // Users now manually clock in/out using the time clock widget
        
        try {
            return $next($request);
        } catch (\Throwable $e) {
            // Log the error to help debug the foreach() issue
            \Illuminate\Support\Facades\Log::error('TimeTrackingMiddleware error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to let Laravel handle it normally
            throw $e;
        }
    }
}
