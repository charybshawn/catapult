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
        if (auth()->check()) {
            $user = auth()->user();
            
            // Check if user already has an active time card for today
            $activeTimeCard = TimeCard::getActiveForUser($user->id);
            
            if (!$activeTimeCard) {
                // Create a new time card for today
                TimeCard::create([
                    'user_id' => $user->id,
                    'clock_in' => now(),
                    'work_date' => today(),
                    'status' => 'active',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        }
        
        return $next($request);
    }
}
