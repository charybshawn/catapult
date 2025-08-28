<?php

namespace App\Traits\Logging;

use Exception;
use App\Services\ApiLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Logs API Requests Trait
 * 
 * Comprehensive API request and response logging for agricultural controllers
 * and services. Provides detailed API activity tracking essential for agricultural
 * system integration monitoring and debugging.
 * 
 * @logging_trait API request/response logging for agricultural system integrations
 * @agricultural_use API logging for agricultural data exchanges, webhook processing, external integrations
 * @monitoring API performance monitoring and debugging for agricultural system interfaces
 * @security API authentication and rate limiting tracking for agricultural system security
 * 
 * Key features:
 * - Complete API request/response cycle logging for agricultural endpoints
 * - Error logging with context for agricultural API troubleshooting
 * - Authentication attempt tracking for agricultural system security
 * - Rate limiting monitoring for agricultural API protection
 * - Webhook activity logging for agricultural system integrations
 * - External API call tracking for agricultural service dependencies
 * 
 * @package App\Traits\Logging
 * @author Shawn
 * @since 2024
 */
trait LogsApiRequests
{
    /**
     * Log an incoming API request for agricultural system endpoints.
     * 
     * @agricultural_context Log API requests for agricultural data endpoints and integrations
     * @param Request $request Incoming HTTP request for agricultural API endpoint
     * @param string|null $endpoint Custom endpoint name for agricultural API logging
     * @return string Request ID for correlation with response and error logs
     */
    public function logApiRequest(Request $request, string $endpoint = null): string
    {
        return app(ApiLogService::class)->logRequest(
            $request,
            $endpoint ?? $request->path(),
            static::class
        );
    }

    /**
     * Log an API response.
     */
    public function logApiResponse(string $requestId, $response, int $statusCode = 200): void
    {
        app(ApiLogService::class)->logResponse(
            $requestId,
            $response,
            $statusCode,
            static::class
        );
    }

    /**
     * Log an API error.
     */
    public function logApiError(string $requestId, Exception $exception): void
    {
        app(ApiLogService::class)->logError(
            $requestId,
            $exception,
            static::class
        );
    }

    /**
     * Log API rate limiting.
     */
    public function logApiRateLimit(Request $request, int $limit, int $remaining): void
    {
        activity('api_rate_limit')
            ->withProperties([
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'limit' => $limit,
                'remaining' => $remaining,
                'user_agent' => $request->userAgent(),
            ])
            ->log('API rate limit checked');
    }

    /**
     * Log API authentication attempt.
     */
    public function logApiAuthentication(Request $request, bool $success, string $method = 'token'): void
    {
        activity('api_authentication')
            ->withProperties([
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'success' => $success,
                'method' => $method,
                'user_agent' => $request->userAgent(),
            ])
            ->log($success ? 'API authentication successful' : 'API authentication failed');
    }

    /**
     * Wrap an agricultural API action with comprehensive request/response logging.
     * 
     * @agricultural_context Complete logging wrapper for agricultural API endpoints
     * @param Request $request HTTP request for agricultural API action
     * @param callable $action Agricultural API action to execute with logging
     * @return mixed API response with automatic logging of success or failure
     * @error_handling Automatically logs exceptions for agricultural API troubleshooting
     */
    public function withApiLogging(Request $request, callable $action)
    {
        $requestId = $this->logApiRequest($request);

        try {
            $response = $action();
            
            $statusCode = $response instanceof JsonResponse ? $response->getStatusCode() : 200;
            $this->logApiResponse($requestId, $response, $statusCode);
            
            return $response;
        } catch (Exception $e) {
            $this->logApiError($requestId, $e);
            throw $e;
        }
    }

    /**
     * Log API webhook activity for agricultural system integrations.
     * 
     * @agricultural_context Webhook logging for agricultural system integration monitoring
     * @param string $type Webhook type identifier for agricultural system classification
     * @param Request $request Incoming webhook request from agricultural integration partner
     * @param array $payload Webhook payload data for agricultural system processing
     * @return void
     */
    public function logWebhook(string $type, Request $request, array $payload = []): void
    {
        activity('api_webhook')
            ->withProperties([
                'type' => $type,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'payload' => $payload,
                'ip' => $request->ip(),
            ])
            ->log("Webhook received: {$type}");
    }

    /**
     * Log external API call for agricultural service dependencies.
     * 
     * @agricultural_context External API call logging for agricultural system dependencies
     * @param string $service External service name (seed suppliers, weather services, etc.)
     * @param string $endpoint External API endpoint for agricultural data integration
     * @param array $parameters Request parameters sent to external agricultural service
     * @param mixed|null $response Response received from external agricultural service
     * @return void
     */
    public function logExternalApiCall(string $service, string $endpoint, array $parameters = [], $response = null): void
    {
        activity('external_api_call')
            ->withProperties([
                'service' => $service,
                'endpoint' => $endpoint,
                'parameters' => $parameters,
                'response_received' => !is_null($response),
                'response_size' => is_string($response) ? strlen($response) : null,
            ])
            ->log("External API call to {$service}");
    }
}