<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate unique request ID
        $requestId = $request->headers->get('X-Request-ID', Str::uuid()->toString());
        $request->headers->set('X-Request-ID', $requestId);

        // Store request start time
        $request->attributes->set('request_start_time', microtime(true));

        // Log incoming request
        $this->logIncomingRequest($request, $requestId);

        // Process the request
        $response = $next($request);

        // Log outgoing response
        $this->logOutgoingResponse($request, $response, $requestId);

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Log incoming request.
     */
    protected function logIncomingRequest(Request $request, string $requestId): void
    {
        $properties = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
            'user_id' => $request->user()?->id,
            'session_id' => session()->getId(),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
            'content_type' => $request->getContentType(),
            'content_length' => $request->header('Content-Length', 0),
        ];

        // Add route information if available
        if ($route = $request->route()) {
            $properties['route_name'] = $route->getName();
            $properties['route_action'] = $route->getActionName();
            $properties['route_parameters'] = $route->parameters();
        }

        activity('http_request')
            ->causedBy($request->user())
            ->withProperties($properties)
            ->log('HTTP request received');
    }

    /**
     * Log outgoing response.
     */
    protected function logOutgoingResponse(Request $request, Response $response, string $requestId): void
    {
        $startTime = $request->attributes->get('request_start_time');
        $duration = $startTime ? (microtime(true) - $startTime) * 1000 : null;

        $properties = [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'memory_usage' => memory_get_peak_usage(true),
            'response_size' => strlen($response->getContent()),
            'cache_control' => $response->headers->get('Cache-Control'),
        ];

        // Log based on status code
        $logName = 'http_response';
        $description = 'HTTP response sent';

        if ($response->getStatusCode() >= 500) {
            $logName = 'http_error';
            $description = 'Server error response';
            $properties['error_type'] = 'server_error';
        } elseif ($response->getStatusCode() >= 400) {
            $logName = 'http_error';
            $description = 'Client error response';
            $properties['error_type'] = 'client_error';
        } elseif ($response->getStatusCode() >= 300) {
            $properties['redirect_location'] = $response->headers->get('Location');
        }

        activity($logName)
            ->causedBy($request->user())
            ->withProperties($properties)
            ->log($description);

        // Log slow requests
        if ($duration && $duration > config('logging.slow_request_threshold_ms', 1000)) {
            $this->logSlowRequest($request, $response, $duration, $requestId);
        }

        // Log high memory usage
        $memoryLimit = $this->getMemoryLimitInBytes();
        $memoryUsage = memory_get_peak_usage(true);
        if ($memoryLimit > 0 && ($memoryUsage / $memoryLimit) > 0.8) {
            $this->logHighMemoryUsage($request, $memoryUsage, $memoryLimit, $requestId);
        }
    }

    /**
     * Log slow request.
     */
    protected function logSlowRequest(Request $request, Response $response, float $duration, string $requestId): void
    {
        activity('slow_request')
            ->withProperties([
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'threshold_ms' => config('logging.slow_request_threshold_ms', 1000),
                'status_code' => $response->getStatusCode(),
                'route_action' => $request->route()?->getActionName(),
            ])
            ->log('Slow request detected');
    }

    /**
     * Log high memory usage.
     */
    protected function logHighMemoryUsage(Request $request, int $memoryUsage, int $memoryLimit, string $requestId): void
    {
        activity('high_memory_usage')
            ->withProperties([
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'memory_usage' => $memoryUsage,
                'memory_limit' => $memoryLimit,
                'usage_percentage' => ($memoryUsage / $memoryLimit) * 100,
                'route_action' => $request->route()?->getActionName(),
            ])
            ->log('High memory usage detected');
    }

    /**
     * Get memory limit in bytes.
     */
    protected function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === '-1') {
            return -1;
        }

        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * Terminate callback for handling fatal errors.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Additional cleanup or logging after response is sent
        $requestId = $request->headers->get('X-Request-ID');
        
        if ($requestId && $error = error_get_last()) {
            if ($error['type'] === E_ERROR || $error['type'] === E_PARSE) {
                activity('fatal_error')
                    ->withProperties([
                        'request_id' => $requestId,
                        'error' => $error,
                        'url' => $request->fullUrl(),
                    ])
                    ->log('Fatal error occurred');
            }
        }
    }
}