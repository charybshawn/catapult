<?php

namespace App\Traits\Logging;

use App\Services\ApiLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait LogsApiRequests
{
    /**
     * Log an incoming API request.
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
    public function logApiError(string $requestId, \Exception $exception): void
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
     * Wrap an API action with logging.
     */
    public function withApiLogging(Request $request, callable $action)
    {
        $requestId = $this->logApiRequest($request);

        try {
            $response = $action();
            
            $statusCode = $response instanceof JsonResponse ? $response->getStatusCode() : 200;
            $this->logApiResponse($requestId, $response, $statusCode);
            
            return $response;
        } catch (\Exception $e) {
            $this->logApiError($requestId, $e);
            throw $e;
        }
    }

    /**
     * Log API webhook activity.
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
     * Log external API call.
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