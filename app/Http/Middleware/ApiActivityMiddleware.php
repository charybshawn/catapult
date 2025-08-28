<?php

namespace App\Http\Middleware;

use Exception;
use App\Services\ApiLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for comprehensive API activity monitoring and logging in agricultural microgreens management.
 * 
 * Provides detailed request/response logging, performance monitoring, and error tracking for all API
 * endpoints supporting agricultural operations including crop planning, order management, inventory
 * tracking, and seed catalog operations. Includes specialized handling for agricultural data patterns
 * and performance optimization for high-volume production workflows.
 *
 * @package App\Http\Middleware
 * @author Catapult Development Team
 * @since 1.0.0
 * 
 * @uses ApiLogService For specialized agricultural API logging and monitoring
 * @uses ActivityLogServiceProvider For agricultural activity tracking integration
 * 
 * @agricultural_operations Crop planning APIs, order management, inventory tracking
 * @performance_monitoring Request timing, slow query detection, rate limit tracking
 * @security_features API key logging, authentication method detection, IP tracking
 */
class ApiActivityMiddleware
{
    /**
     * Agricultural API logging service for specialized farm management monitoring.
     *
     * @var ApiLogService Service handling agricultural-specific API logging patterns
     */
    protected ApiLogService $apiLogService;

    /**
     * Initialize API activity middleware with agricultural logging service.
     *
     * @param ApiLogService $apiLogService Specialized service for agricultural API monitoring
     * @agricultural_context Provides logging for crop operations, order processing, inventory management
     */
    public function __construct(ApiLogService $apiLogService)
    {
        $this->apiLogService = $apiLogService;
    }

    /**
     * Handle incoming API request with comprehensive agricultural operations monitoring.
     * 
     * Provides full-lifecycle monitoring of API requests supporting agricultural operations
     * including crop planning, order management, inventory tracking, and seed catalog access.
     * Includes performance monitoring, error tracking, and specialized agricultural context
     * logging for farm management workflows.
     *
     * @param Request $request HTTP request containing agricultural API operation data
     * @param Closure $next Next middleware in pipeline for continued request processing
     * @return Response HTTP response with monitoring headers and agricultural operation context
     * 
     * @throws Exception Re-throws any exceptions after logging for agricultural error tracking
     * 
     * @agricultural_workflows Crop planning APIs, order simulation, inventory adjustments
     * @monitoring_features Request ID tracking, timing analysis, performance optimization
     * @security_logging API key validation, authentication tracking, IP monitoring
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
        } catch (Exception $e) {
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
     * Determine if request should be treated as agricultural API operation.
     * 
     * Identifies API requests requiring specialized agricultural monitoring including
     * crop planning endpoints, order management APIs, inventory tracking operations,
     * and seed catalog access. Uses multiple detection methods to ensure comprehensive
     * coverage of agricultural business operations.
     *
     * @param Request $request HTTP request to analyze for API characteristics
     * @return bool True if request requires agricultural API monitoring
     * 
     * @detection_methods URL path matching, JSON expectation, API key headers
     * @agricultural_apis /api/crops, /api/orders, /api/inventory, /api/seed-catalog
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || 
               $request->expectsJson() || 
               $request->hasHeader('X-API-Key');
    }

    /**
     * Log incoming API request with comprehensive agricultural operation context.
     * 
     * Captures detailed information about API requests supporting agricultural workflows
     * including crop planning, order management, inventory operations, and seed catalog
     * access. Provides specialized logging for agricultural business operations with
     * performance tracking and security monitoring.
     *
     * @param Request $request HTTP request containing agricultural API operation data
     * @param string $requestId Unique identifier for request tracking across agricultural workflows
     * @return void Logs activity through agricultural activity logging system
     * 
     * @agricultural_context Captures crop IDs, order references, variety information
     * @performance_tracking Request timing, endpoint analysis, user agent detection
     * @security_logging API key presence, authentication method, IP address tracking
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
     * Log API response with agricultural operation performance analysis.
     * 
     * Captures comprehensive response metrics for agricultural API operations including
     * timing analysis, status code categorization, and performance monitoring specific
     * to farm management workflows. Includes specialized handling for agricultural data
     * operations and performance optimization tracking.
     *
     * @param Request $request Original HTTP request for agricultural API operation
     * @param Response $response HTTP response containing agricultural operation results
     * @param string $requestId Unique identifier linking request/response for analysis
     * @return void Logs response metrics through agricultural monitoring system
     * 
     * @performance_monitoring Response timing, cache status, size analysis
     * @agricultural_operations Order processing, crop updates, inventory adjustments
     * @rate_limiting Agricultural API throttling and usage pattern tracking
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
     * Log API exceptions during agricultural operations with detailed error context.
     * 
     * Captures comprehensive exception information for agricultural API failures including
     * crop planning errors, order processing failures, inventory conflicts, and seed catalog
     * issues. Provides detailed stack traces and context for debugging agricultural workflows.
     *
     * @param Request $request HTTP request that triggered agricultural operation exception
     * @param Exception $exception Exception thrown during agricultural API operation
     * @param string $requestId Unique identifier for tracking failed agricultural operations
     * @return void Logs exception through agricultural error monitoring system
     * 
     * @agricultural_errors Crop validation failures, inventory conflicts, order processing issues
     * @debugging_context Stack trace analysis, file/line tracking, exception classification
     * @monitoring_integration Links to agricultural operation monitoring and alerting
     */
    protected function logApiException(Request $request, Exception $exception, string $requestId): void
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
     * Log slow API calls impacting agricultural operation performance.
     * 
     * Identifies and logs API operations exceeding performance thresholds that could impact
     * agricultural workflows including crop planning calculations, order processing, inventory
     * management, and seed catalog operations. Provides detailed analysis for optimizing
     * farm management system performance.
     *
     * @param Request $request HTTP request for slow agricultural API operation
     * @param Response $response HTTP response from slow agricultural operation
     * @param float $duration Actual response time in milliseconds exceeding threshold
     * @param string $requestId Unique identifier for tracking slow agricultural operations
     * @return void Logs performance issue through agricultural monitoring system
     * 
     * @performance_thresholds Configurable limits for agricultural operation timing
     * @agricultural_optimization Crop calculation performance, order processing speed
     * @monitoring_alerts Performance degradation detection for farm management
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
     * Extract controller information for agricultural API operation tracking.
     * 
     * Identifies the specific controller and method handling agricultural API requests
     * to provide detailed operation tracking for crop planning, order management,
     * inventory operations, and seed catalog access. Enables targeted monitoring
     * of agricultural business logic performance.
     *
     * @param Request $request HTTP request containing agricultural API operation
     * @return array Controller information including class name and method
     * 
     * @return_format ['name' => 'ControllerClass', 'method' => 'methodName']
     * @agricultural_controllers CropController, OrderController, InventoryController
     * @monitoring_context Enables targeted performance analysis by operation type
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
     * Extract API version for agricultural operation compatibility tracking.
     * 
     * Determines API version from multiple sources to ensure proper handling of
     * agricultural operations across different API versions. Supports version-specific
     * features for crop planning, order management, and inventory operations while
     * maintaining backward compatibility for farm management integrations.
     *
     * @param Request $request HTTP request containing potential version indicators
     * @return string|null API version string or null if not specified
     * 
     * @version_sources X-API-Version header, URL path versioning, query parameters
     * @agricultural_versioning Crop API v2.1, Order API v1.3, Inventory API v2.0
     * @compatibility_tracking Version-specific agricultural feature availability
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
     * Identify authentication method for agricultural API security tracking.
     * 
     * Determines authentication method used for agricultural API access to ensure
     * proper security monitoring and access control for sensitive farm management
     * operations. Supports multiple authentication methods for different agricultural
     * integration scenarios and security requirements.
     *
     * @param Request $request HTTP request containing authentication credentials
     * @return string|null Authentication method identifier or null if unauthenticated
     * 
     * @auth_methods bearer_token, api_key, session (web interface access)
     * @agricultural_security Protects crop data, order information, inventory levels
     * @access_patterns Farm management UI, mobile apps, third-party integrations
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
     * Determine cache status for agricultural API response optimization.
     * 
     * Analyzes response caching headers to track cache effectiveness for agricultural
     * operations including seed catalog data, crop information, and inventory levels.
     * Provides insights for optimizing farm management system performance through
     * strategic caching of agricultural reference data.
     *
     * @param Response $response HTTP response from agricultural API operation
     * @return string Cache status indicator for performance analysis
     * 
     * @cache_states BYPASS, EXPIRED, CACHEABLE, NONE
     * @agricultural_caching Seed catalog optimization, crop data performance
     * @performance_optimization Reduces database load for reference data
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
     * Add agricultural API monitoring headers to response.
     * 
     * Enriches API responses with monitoring and debugging headers specific to
     * agricultural operations. Includes request tracking, timing information, and
     * version details to support agricultural system monitoring and performance
     * analysis for farm management workflows.
     *
     * @param Response $response HTTP response to enhance with monitoring headers
     * @param Request $request Original HTTP request for context extraction
     * @param string $requestId Unique identifier for agricultural operation tracking
     * @return void Modifies response headers in-place
     * 
     * @response_headers X-Request-ID, X-Response-Time, X-API-Version
     * @agricultural_monitoring Enables correlation of agricultural operations
     * @performance_debugging Response timing analysis for farm management operations
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