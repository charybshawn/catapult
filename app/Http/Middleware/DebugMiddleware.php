<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Debug middleware for comprehensive agricultural system error tracking and analysis.
 * 
 * Provides detailed error logging and debugging capabilities specifically tailored for
 * agricultural microgreens management operations. Captures comprehensive error context
 * for crop planning failures, order processing issues, inventory conflicts, and seed
 * catalog problems to enable rapid troubleshooting of farm management workflows.
 *
 * @package App\Http\Middleware
 * @author Catapult Development Team
 * @since 1.0.0
 * 
 * @debugging_features Exception capture, stack trace analysis, request context logging
 * @agricultural_errors Crop validation failures, order processing issues, inventory conflicts
 * @performance_monitoring Error frequency analysis, agricultural operation failure patterns
 * 
 * @security_consideration Sensitive data filtering, agricultural business data protection
 * @development_mode Enhanced error display for agricultural system development
 */
class DebugMiddleware
{
    /**
     * Handle request with comprehensive agricultural system error monitoring.
     * 
     * Wraps request processing with detailed error capture and logging specific to
     * agricultural operations. Provides comprehensive context for debugging crop
     * planning issues, order processing failures, inventory conflicts, and seed
     * catalog problems while protecting sensitive agricultural business data.
     *
     * @param Request $request HTTP request for agricultural system operation
     * @param Closure $next Next middleware in pipeline for continued processing
     * @return mixed Response or re-thrown exception with enhanced agricultural debugging
     * 
     * @throws Throwable Re-throws original exception after comprehensive logging
     * 
     * @agricultural_debugging Crop operation failures, order processing errors
     * @error_context Request details, user context, agricultural operation parameters
     * @development_support Enhanced error display for agricultural system debugging
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            // Log detailed error information
            Log::error('DebugMiddleware caught exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->formatTrace($e->getTrace()),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'input' => $this->formatInput($request->all()),
            ]);
            
            // Create a more detailed error page in debug mode
            if (config('app.debug')) {
                return response()->view('errors.debug', [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request' => $request,
                ], 500);
            }
            
            // Re-throw the exception to let Laravel handle it normally
            throw $e;
        }
    }
    
    /**
     * Format stack trace for agricultural system debugging and analysis.
     * 
     * Processes exception stack traces to provide clear, readable debugging information
     * for agricultural system failures including crop planning errors, order processing
     * issues, and inventory management problems. Limits trace depth and summarizes
     * function arguments to prevent information overload while maintaining debugging value.
     *
     * @param array $trace Raw exception stack trace from agricultural operation failure
     * @return array Formatted trace with file, line, function, and argument information
     * 
     * @agricultural_context Crop controller methods, order service functions, inventory operations
     * @debugging_optimization Limited to 20 entries for performance and readability
     * @trace_format Includes class, method, file location, and summarized arguments
     */
    private function formatTrace(array $trace): array
    {
        return array_map(function ($item) {
            return [
                'file' => $item['file'] ?? 'Unknown file',
                'line' => $item['line'] ?? 0,
                'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? ''),
                'args' => $this->summarizeArgs($item['args'] ?? []),
            ];
        }, array_slice($trace, 0, 20)); // Limit to first 20 entries
    }
    
    /**
     * Format request input data for secure agricultural system logging.
     * 
     * Processes request input to remove sensitive information while preserving
     * debugging context for agricultural operations. Protects passwords and
     * confidential business data while maintaining visibility into crop planning
     * parameters, order details, and inventory adjustment operations.
     *
     * @param array $input Raw request input data from agricultural operation
     * @return array Sanitized input data suitable for agricultural system logging
     * 
     * @security_features Password removal, sensitive data protection
     * @agricultural_data Crop IDs, variety information, order quantities, inventory levels
     * @debugging_context Preserves operation parameters while protecting credentials
     */
    private function formatInput(array $input): array
    {
        // Remove sensitive data
        unset($input['password'], $input['password_confirmation']);
        
        // Handle nested arrays
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = '[Array with ' . count($value) . ' items]';
            } elseif (is_object($value)) {
                $input[$key] = '[Object of class ' . get_class($value) . ']';
            }
        }
        
        return $input;
    }
    
    /**
     * Summarize function arguments for efficient agricultural system debugging.
     * 
     * Converts function arguments to concise, loggable format suitable for agricultural
     * system debugging without overwhelming log files. Handles complex agricultural
     * objects like crop models, order collections, and inventory transactions while
     * maintaining debugging value and system performance.
     *
     * @param array $args Function arguments from agricultural operation stack trace
     * @return array Summarized arguments with type and size information
     * 
     * @agricultural_objects Crop models, Order collections, Product instances
     * @performance_optimization String truncation, object type identification
     * @debugging_balance Detailed enough for troubleshooting, concise for performance
     */
    private function summarizeArgs(array $args): array
    {
        return array_map(function ($arg) {
            if (is_null($arg)) {
                return 'null';
            } elseif (is_scalar($arg)) {
                return is_string($arg) ? (strlen($arg) > 50 ? substr($arg, 0, 47) . '...' : $arg) : $arg;
            } elseif (is_array($arg)) {
                return '[Array with ' . count($arg) . ' items]';
            } elseif (is_object($arg)) {
                return '[Object of class ' . get_class($arg) . ']';
            }
            return gettype($arg);
        }, $args);
    }
} 