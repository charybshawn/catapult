<?php

namespace App\Listeners;

use Exception;
use App\Services\ApiLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Comprehensive API request monitoring and logging listener for agricultural system.
 * 
 * Provides detailed logging of all API interactions including request/response lifecycle,
 * performance monitoring, error tracking, and security auditing. Essential for monitoring
 * agricultural API usage patterns, detecting performance issues, and maintaining
 * security compliance in microgreens production management system.
 * 
 * @business_domain API monitoring for agricultural operations and third-party integrations
 * @security_features Request sanitization, sensitive header redaction, intrusion detection
 * @performance_monitoring Response time tracking, slow query detection, rate limiting
 * @agricultural_context Monitors crop data APIs, order processing, inventory management
 */
class ApiRequestListener
{
    /**
     * Service for structured API logging and analytics.
     * 
     * @var ApiLogService Service handling API request/response logging
     */
    protected ApiLogService $apiLogService;
    
    /**
     * API endpoints to exclude from logging (health checks, status endpoints).
     * 
     * @var array<string> Endpoint patterns to skip during logging
     */
    protected array $excludedEndpoints = [];
    
    /**
     * HTTP headers containing sensitive information to redact from logs.
     * 
     * Security-sensitive headers that must be masked in activity logs to
     * prevent credential exposure in agricultural system monitoring.
     * 
     * @var array<string> Header names to sanitize for security compliance
     */
    protected array $sensitiveHeaders = [
        'authorization',
        'x-api-key',
        'cookie',
        'x-csrf-token',
    ];

    /**
     * Initialize API request listener with logging service and configuration.
     * 
     * @param ApiLogService $apiLogService Service for API request/response logging
     */
    public function __construct(ApiLogService $apiLogService)
    {
        $this->apiLogService = $apiLogService;
        $this->excludedEndpoints = config('logging.excluded_api_endpoints', []);
    }

    /**
     * Log incoming API request with comprehensive context and security information.
     * 
     * Captures detailed request metadata including headers, parameters, body size,
     * user context, and timing information. Sanitizes sensitive headers and creates
     * unique request identifier for correlation with response logging.
     * 
     * @param Request $request HTTP request object to log
     * @return string|null Unique request ID for correlation, null if not logged
     * 
     * @business_process API request lifecycle tracking for agricultural operations
     * @security_context IP tracking, user agent analysis, header sanitization
     * @performance_monitoring Request timing start point for response correlation
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
     * Log API response with performance metrics and error detection.
     * 
     * Captures response metadata including status codes, response time, payload size,
     * cache status, and rate limiting information. Automatically detects slow responses
     * and error conditions for agricultural system monitoring and alerting.
     * 
     * @param Request $request Original request object for correlation
     * @param Response $response HTTP response object with metrics
     * @return void
     * 
     * @performance_analysis Response time calculation and slow query detection
     * @error_monitoring Automatic error response categorization and alerting
     * @agricultural_monitoring Tracks agricultural API performance and reliability
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
     * Log API exceptions with detailed stack trace and context information.
     * 
     * Captures unhandled exceptions in API requests with comprehensive error
     * details including class, message, file location, and request context.
     * Critical for debugging agricultural system API failures and maintaining
     * system reliability.
     * 
     * @param Request $request Request that caused the exception
     * @param Exception $exception Exception object with error details
     * @return void
     * 
     * @error_handling Exception tracking and debugging information capture
     * @agricultural_context API failures in crop management, orders, inventory
     * @security_monitoring Potential security issues via exception patterns
     */
    public function logException(Request $request, Exception $exception): void
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
     * Determine if API request should be logged based on filtering criteria.
     * 
     * Applies filtering logic to reduce log noise from health checks, internal
     * endpoints, and non-API routes. Focuses logging on business-relevant API
     * interactions for agricultural operations monitoring.
     * 
     * @param Request $request Request to evaluate for logging eligibility
     * @return bool True if request should be logged, false to skip
     * 
     * @business_rule Only log actual API routes, exclude system health checks
     * @performance_optimization Reduces log volume by filtering noise
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
     * Extract controller class name from Laravel route for API categorization.
     * 
     * Determines which controller is handling the API request for better
     * categorization and monitoring of agricultural system endpoints.
     * 
     * @param Request $request Request object to analyze for controller information
     * @return string|null Fully qualified controller class name or null if not found
     * 
     * @route_analysis Laravel route inspection for API endpoint categorization
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
     * Sanitize HTTP headers by redacting sensitive authentication information.
     * 
     * Removes or masks security-sensitive headers like authorization tokens,
     * API keys, and session cookies to prevent credential exposure in logs
     * while maintaining audit trail for agricultural system security.
     * 
     * @param array $headers Raw HTTP headers array
     * @return array Sanitized headers with sensitive data redacted
     * 
     * @security_compliance Prevents credential leakage in agricultural system logs
     * @audit_trail Maintains header structure for security analysis
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
     * Extract API version information from request headers, URL, or parameters.
     * 
     * Supports multiple API versioning strategies for agricultural system APIs
     * including header-based, URL path-based, and query parameter versioning.
     * Essential for API lifecycle management and compatibility tracking.
     * 
     * @param Request $request Request to analyze for version information
     * @return string|null API version string or null if not specified
     * 
     * @api_versioning Multi-strategy version detection for agricultural APIs
     * @compatibility_tracking Monitors API version usage patterns
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
     * Determine HTTP response cache status for performance monitoring.
     * 
     * Analyzes response headers to determine if content was served from cache,
     * bypassed cache, or is cacheable. Important for optimizing agricultural
     * system API performance and reducing database load.
     * 
     * @param Response $response Response object to analyze for cache information
     * @return string Cache status: 'HIT', 'MISS', 'CACHEABLE', or 'UNKNOWN'
     * 
     * @performance_optimization Cache effectiveness monitoring for agricultural APIs
     * @resource_management Database load reduction through effective caching
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
     * Log slow API responses exceeding performance thresholds.
     * 
     * Creates specific activity log entries for API calls that exceed configured
     * performance thresholds. Critical for identifying performance bottlenecks
     * in agricultural system operations and optimizing user experience.
     * 
     * @param Request $request Original request for context
     * @param Response $response Response object with performance data
     * @param float $duration Response time in milliseconds
     * @return void
     * 
     * @performance_alerting Automatic slow response detection and logging
     * @agricultural_optimization Identifies bottlenecks in crop/order processing
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
     * Log API error responses with detailed error context and validation issues.
     * 
     * Captures HTTP error responses (4xx/5xx) with structured error information
     * including validation errors, error codes, and business rule violations.
     * Essential for monitoring agricultural system API reliability.
     * 
     * @param Request $request Original request that caused the error
     * @param Response $response Error response object with error details
     * @return void
     * 
     * @error_tracking Comprehensive API error monitoring and categorization
     * @business_validation Captures agricultural business rule violations
     * @system_reliability API error rate monitoring and alerting
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