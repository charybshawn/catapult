<?php

namespace App\Http\Middleware;

use Throwable;
use Illuminate\Support\Facades\Log;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\TimeCard;

/**
 * Time tracking middleware for agricultural workforce management and performance monitoring.
 * 
 * Provides comprehensive time tracking capabilities for agricultural operations including
 * crop cultivation activities, order processing workflows, inventory management tasks,
 * and seed handling operations. Currently configured for manual time tracking through
 * dedicated time clock interfaces while maintaining error monitoring for system stability.
 *
 * @package App\Http\Middleware
 * @author Catapult Development Team
 * @since 1.0.0
 * 
 * @agricultural_tracking Crop cultivation time, order processing duration, inventory tasks
 * @workforce_management Manual clock-in/clock-out through time clock widget
 * @performance_monitoring Agricultural operation timing and productivity analysis
 * 
 * @time_tracking_mode Manual - Users control time tracking through dedicated interfaces
 * @error_monitoring Exception capture for time tracking system stability
 * 
 * @related_models TimeCard For agricultural workforce time tracking records
 */
class TimeTrackingMiddleware
{
    /**
     * Handle request with agricultural time tracking monitoring and error capture.
     * 
     * Processes requests while maintaining time tracking system stability for agricultural
     * workforce management. Currently operates in manual mode where agricultural workers
     * use dedicated time clock interfaces to track crop cultivation, order processing,
     * and inventory management activities. Includes comprehensive error logging for
     * time tracking system troubleshooting.
     *
     * @param Request $request HTTP request from agricultural workforce management interface
     * @param Closure $next Next middleware in pipeline for continued request processing
     * @return Response HTTP response with time tracking error monitoring
     * 
     * @throws Throwable Re-throws exceptions after logging for agricultural system stability
     * 
     * @workforce_context Manual time tracking for agricultural operations
     * @error_handling Comprehensive logging for time tracking system debugging
     * @agricultural_activities Crop work, order fulfillment, inventory management
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Automatic time tracking has been disabled
        // Users now manually clock in/out using the time clock widget
        
        try {
            return $next($request);
        } catch (Throwable $e) {
            // Log the error to help debug the foreach() issue
            Log::error('TimeTrackingMiddleware error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to let Laravel handle it normally
            throw $e;
        }
    }
}
