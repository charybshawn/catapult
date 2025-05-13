<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
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
     * Format the trace to be more readable
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
     * Format request input data for logging
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
     * Summarize function arguments for logging
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