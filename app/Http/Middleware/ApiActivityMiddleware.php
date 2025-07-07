<?php

namespace App\Http\Middleware;

use App\Services\ApiLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiActivityMiddleware
{
    protected ApiLogService $apiLogService;

    public function __construct(ApiLogService $apiLogService)
    {
        $this->apiLogService = $apiLogService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if not an API request
        if (!$this->isApiRequest($request)) {
            return $next($request);
        }

        // Generate or retrieve request ID
        $requestId = $request->headers->get('X-Request-ID', Str::uuid()->toString());
        $request->headers->set('X-Request-ID', $requestId);

        // Store API context
        $request->attributes->set('api_request_id', $requestId);
        $request->attributes->set('api_start_time', microtime(true));

        // Log API request
        $this->logApiRequest($request, $requestId);

        // Handle the request
        try {
            $response = $next($request);
        } catch (\Exception $e) {
            $this->logApiException($request, $e, $requestId);
            throw $e;
        }

        // Log API response
        $this->logApiResponse($request, $response, $requestId);

        // Add API headers to response
        $this->addApiHeaders($response, $request, $requestId);

        return $response;
    }

    /**
     * Check if this is an API request.
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || 
               $request->expectsJson() || 
               $request->hasHeader('X-API-Key');
    }

    /**
     * Log API request.
     */
    protected function logApiRequest(Request $request, string $requestId): void
    {
        $endpoint = $request->path();
        $controller = $this->getControllerInfo($request);

        activity('api_request')
            ->causedBy($request->user())
            ->withProperties([
                'request_id' => $requestId,
                'endpoint' => $endpoint,
                'method' => $request->method(),
                'full_url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'api_key' => $request->hasHeader('X-API-Key') ? '***present***' : null,
                'content_type' => $request->getContentType(),
                'accept' => $request->header('Accept'),
                'api_version' => $this->extractApiVersion($request),
                'controller' => $controller['name'] ?? null,
                'action' => $controller['method'] ?? null,
                'authenticated' => $request->user() !== null,
                'auth_method' => $this->getAuthMethod($request),
            ])
            ->log("API request: {$request->method()} {$endpoint}");
    }

    /**
     * Log API response.
     */
    protected function logApiResponse(Request $request, Response $response, string $requestId): void
    {
        $startTime = $request->attributes->get('api_start_time');
        $duration = $startTime ? (microtime(true) - $startTime) * 1000 : null;

        $properties = [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
            'content_type' => $response->headers->get('Content-Type'),
            'cache_status' => $this->getCacheStatus($response),
        ];

        // Add rate limiting info if available
        if ($response->headers->has('X-RateLimit-Limit')) {
            $properties['rate_limit'] = [
                'limit' => $response->headers->get('X-RateLimit-Limit'),
                'remaining' => $response->headers->get('X-RateLimit-Remaining'),
                'reset' => $response->headers->get('X-RateLimit-Reset'),
            ];
        }

        // Determine log level based on status code
        $logName = 'api_response';
        if ($response->getStatusCode() >= 500) {
            $logName = 'api_error';
        } elseif ($response->getStatusCode() === 429) {
            $logName = 'api_rate_limited';
        } elseif ($response->getStatusCode() >= 400) {
            $logName = 'api_client_error';
        }

        activity($logName)
            ->causedBy($request->user())
            ->withProperties($properties)
            ->log("API response: {$response->getStatusCode()}");

        // Log performance issues
        if ($duration && $duration > config('logging.slow_api_threshold_ms', 2000)) {
            $this->logSlowApiCall($request, $response, $duration, $requestId);
        }
    }

    /**
     * Log API exception.
     */
    protected function logApiException(Request $request, \Exception $exception, string $requestId): void
    {
        activity('api_exception')
            ->causedBy($request->user())
            ->withProperties([
                'request_id' => $requestId,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ])
            ->log("API exception: {$exception->getMessage()}");
    }

    /**
     * Log slow API call.
     */
    protected function logSlowApiCall(Request $request, Response $response, float $duration, string $requestId): void
    {
        activity('api_performance')
            ->withProperties([
                'request_id' => $requestId,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'threshold_ms' => config('logging.slow_api_threshold_ms', 2000),
                'status_code' => $response->getStatusCode(),
                'query_params' => !empty($request->query()) ? array_keys($request->query()) : null,
            ])
            ->log('Slow API call detected');
    }

    /**
     * Get controller information.
     */
    protected function getControllerInfo(Request $request): array
    {
        $route = $request->route();
        if (!$route) {
            return [];
        }

        $action = $route->getAction();
        if (isset($action['controller'])) {
            [$controller, $method] = explode('@', $action['controller']);
            return [
                'name' => $controller,
                'method' => $method,
            ];
        }

        return [];
    }

    /**
     * Extract API version.
     */
    protected function extractApiVersion(Request $request): ?string
    {
        // From header
        if ($version = $request->header('X-API-Version')) {
            return $version;
        }

        // From URL
        if (preg_match('/api\/v(\d+(?:\.\d+)?)/', $request->path(), $matches)) {
            return $matches[1];
        }

        // From query parameter
        return $request->query('api_version');
    }

    /**
     * Get authentication method.
     */
    protected function getAuthMethod(Request $request): ?string
    {
        if ($request->bearerToken()) {
            return 'bearer_token';
        }

        if ($request->hasHeader('X-API-Key')) {
            return 'api_key';
        }

        if ($request->user()) {
            return 'session';
        }

        return null;
    }

    /**
     * Get cache status.
     */
    protected function getCacheStatus(Response $response): string
    {
        if ($response->headers->has('X-Cache')) {
            return $response->headers->get('X-Cache');
        }

        $cacheControl = $response->headers->get('Cache-Control', '');
        
        if (str_contains($cacheControl, 'no-cache') || str_contains($cacheControl, 'no-store')) {
            return 'BYPASS';
        }

        if (str_contains($cacheControl, 'max-age=0')) {
            return 'EXPIRED';
        }

        if (str_contains($cacheControl, 'max-age')) {
            return 'CACHEABLE';
        }

        return 'NONE';
    }

    /**
     * Add API headers to response.
     */
    protected function addApiHeaders(Response $response, Request $request, string $requestId): void
    {
        $response->headers->set('X-Request-ID', $requestId);
        
        // Add timing header
        if ($startTime = $request->attributes->get('api_start_time')) {
            $duration = (microtime(true) - $startTime) * 1000;
            $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        }

        // Add version header if available
        if ($version = $this->extractApiVersion($request)) {
            $response->headers->set('X-API-Version', $version);
        }
    }
}