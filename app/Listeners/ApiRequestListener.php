<?php

namespace App\Listeners;

use App\Services\ApiLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ApiRequestListener
{
    protected ApiLogService $apiLogService;
    protected array $excludedEndpoints = [];
    protected array $sensitiveHeaders = [
        'authorization',
        'x-api-key',
        'cookie',
        'x-csrf-token',
    ];

    public function __construct(ApiLogService $apiLogService)
    {
        $this->apiLogService = $apiLogService;
        $this->excludedEndpoints = config('logging.excluded_api_endpoints', []);
    }

    /**
     * Log API request.
     */
    public function logRequest(Request $request): ?string
    {
        if (!$this->shouldLogRequest($request)) {
            return null;
        }

        $requestId = Str::uuid()->toString();
        $controller = $this->getControllerName($request);

        // Store request context for later use
        $request->attributes->set('api_request_id', $requestId);
        $request->attributes->set('api_request_start', microtime(true));

        activity('api_request_received')
            ->withProperties([
                'request_id' => $requestId,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'full_url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'query_params' => $request->query->all(),
                'body_size' => strlen($request->getContent()),
                'controller' => $controller,
                'user_id' => $request->user()?->id,
                'api_version' => $this->extractApiVersion($request),
            ])
            ->log('API request received');

        return $requestId;
    }

    /**
     * Log API response.
     */
    public function logResponse(Request $request, Response $response): void
    {
        $requestId = $request->attributes->get('api_request_id');
        if (!$requestId) {
            return;
        }

        $startTime = $request->attributes->get('api_request_start');
        $duration = $startTime ? (microtime(true) - $startTime) * 1000 : null;

        activity('api_response_sent')
            ->withProperties([
                'request_id' => $requestId,
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'response_size' => strlen($response->getContent()),
                'headers_sent' => $this->sanitizeHeaders($response->headers->all()),
                'cache_status' => $this->getCacheStatus($response),
                'rate_limit_remaining' => $response->headers->get('X-RateLimit-Remaining'),
            ])
            ->log('API response sent');

        // Log slow API calls
        if ($duration && $duration > config('logging.slow_api_threshold_ms', 2000)) {
            $this->logSlowApiCall($request, $response, $duration);
        }

        // Log error responses
        if ($response->getStatusCode() >= 400) {
            $this->logErrorResponse($request, $response);
        }
    }

    /**
     * Log API exception.
     */
    public function logException(Request $request, \Exception $exception): void
    {
        $requestId = $request->attributes->get('api_request_id');

        activity('api_exception')
            ->withProperties([
                'request_id' => $requestId,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
            ])
            ->log('API exception occurred');
    }

    /**
     * Determine if request should be logged.
     */
    protected function shouldLogRequest(Request $request): bool
    {
        // Skip non-API routes
        if (!$request->is('api/*')) {
            return false;
        }

        // Skip excluded endpoints
        foreach ($this->excludedEndpoints as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        // Skip health check endpoints
        if ($request->is('*/health', '*/ping', '*/status')) {
            return false;
        }

        return true;
    }

    /**
     * Get controller name from request.
     */
    protected function getControllerName(Request $request): ?string
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $controller = $route->getController();
        if (!$controller) {
            return null;
        }

        return get_class($controller);
    }

    /**
     * Sanitize headers for logging.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        foreach ($this->sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Extract API version from request.
     */
    protected function extractApiVersion(Request $request): ?string
    {
        // Check header
        if ($version = $request->header('X-API-Version')) {
            return $version;
        }

        // Check URL path
        if (preg_match('/api\/v(\d+)/', $request->path(), $matches)) {
            return $matches[1];
        }

        // Check query parameter
        if ($version = $request->query('api_version')) {
            return $version;
        }

        return null;
    }

    /**
     * Get cache status from response.
     */
    protected function getCacheStatus(Response $response): string
    {
        if ($response->headers->has('X-Cache')) {
            return $response->headers->get('X-Cache');
        }

        if ($response->headers->has('Cache-Control')) {
            $cacheControl = $response->headers->get('Cache-Control');
            if (str_contains($cacheControl, 'no-cache')) {
                return 'MISS';
            }
            if (str_contains($cacheControl, 'max-age')) {
                return 'CACHEABLE';
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Log slow API call.
     */
    protected function logSlowApiCall(Request $request, Response $response, float $duration): void
    {
        activity('api_slow_response')
            ->withProperties([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'status_code' => $response->getStatusCode(),
                'threshold_ms' => config('logging.slow_api_threshold_ms', 2000),
            ])
            ->log('Slow API response detected');
    }

    /**
     * Log error response.
     */
    protected function logErrorResponse(Request $request, Response $response): void
    {
        $content = json_decode($response->getContent(), true);

        activity('api_error_response')
            ->withProperties([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'error_message' => $content['message'] ?? null,
                'error_code' => $content['code'] ?? null,
                'validation_errors' => $content['errors'] ?? null,
            ])
            ->log('API error response sent');
    }
}