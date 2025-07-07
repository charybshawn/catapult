<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ApiLogService
{
    /**
     * Log an API request.
     */
    public function logRequest(Request $request, string $endpoint, string $controller = null): string
    {
        $requestId = Str::uuid()->toString();
        
        $properties = [
            'request_id' => $requestId,
            'endpoint' => $endpoint,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query(),
            'body' => $this->sanitizeBody($request->all()),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'controller' => $controller,
            'timestamp' => microtime(true),
        ];

        activity('api_request')
            ->withProperties($properties)
            ->log("API request to {$endpoint}");

        // Store request data temporarily for response correlation
        Cache::put("api_request_{$requestId}", $properties, 300);

        return $requestId;
    }

    /**
     * Log an API response.
     */
    public function logResponse(string $requestId, $response, int $statusCode, string $controller = null): void
    {
        $requestData = Cache::get("api_request_{$requestId}");
        $responseTime = $requestData ? microtime(true) - $requestData['timestamp'] : null;

        $properties = [
            'request_id' => $requestId,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'response_size' => $this->getResponseSize($response),
            'response_preview' => $this->getResponsePreview($response),
            'controller' => $controller,
        ];

        activity('api_response')
            ->withProperties($properties)
            ->log("API response with status {$statusCode}");

        // Log performance metrics
        if ($responseTime && $responseTime > config('logging.slow_api_threshold', 2.0)) {
            $this->logSlowApiCall($requestId, $requestData, $responseTime);
        }

        // Clean up cached request data
        Cache::forget("api_request_{$requestId}");
    }

    /**
     * Log an API error.
     */
    public function logError(string $requestId, \Exception $exception, string $controller = null): void
    {
        $requestData = Cache::get("api_request_{$requestId}");

        activity('api_error')
            ->withProperties([
                'request_id' => $requestId,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request_data' => $requestData,
                'controller' => $controller,
            ])
            ->log("API error: {$exception->getMessage()}");

        // Clean up cached request data
        Cache::forget("api_request_{$requestId}");
    }

    /**
     * Log a slow API call.
     */
    protected function logSlowApiCall(string $requestId, ?array $requestData, float $responseTime): void
    {
        activity('slow_api_call')
            ->withProperties([
                'request_id' => $requestId,
                'endpoint' => $requestData['endpoint'] ?? 'unknown',
                'method' => $requestData['method'] ?? 'unknown',
                'response_time' => $responseTime,
                'threshold' => config('logging.slow_api_threshold', 2.0),
            ])
            ->log('Slow API call detected');
    }

    /**
     * Sanitize headers for logging.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie', 'x-csrf-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Sanitize request body for logging.
     */
    protected function sanitizeBody(array $body): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];
        
        array_walk_recursive($body, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '***REDACTED***';
            }
        });

        return $body;
    }

    /**
     * Get response size.
     */
    protected function getResponseSize($response): ?int
    {
        if (is_string($response)) {
            return strlen($response);
        }
        
        if (is_array($response) || is_object($response)) {
            return strlen(json_encode($response));
        }

        return null;
    }

    /**
     * Get response preview.
     */
    protected function getResponsePreview($response, int $maxLength = 500): ?string
    {
        if (is_string($response)) {
            return substr($response, 0, $maxLength);
        }
        
        if (is_array($response) || is_object($response)) {
            $json = json_encode($response);
            return substr($json, 0, $maxLength);
        }

        return null;
    }

    /**
     * Get API statistics.
     */
    public function getStatistics(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): array
    {
        $query = activity()
            ->inLog('api_request');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $requests = $query->get();
        $responses = activity()
            ->inLog('api_response')
            ->whereIn('properties->request_id', $requests->pluck('properties.request_id'))
            ->get();

        return [
            'total_requests' => $requests->count(),
            'successful_responses' => $responses->where('properties.status_code', '>=', 200)
                ->where('properties.status_code', '<', 300)->count(),
            'error_responses' => $responses->where('properties.status_code', '>=', 400)->count(),
            'average_response_time' => $responses->avg('properties.response_time'),
            'endpoints' => $this->getEndpointStatistics($requests, $responses),
            'status_codes' => $responses->groupBy('properties.status_code')
                ->map->count()
                ->sortKeys(),
        ];
    }

    /**
     * Get endpoint statistics.
     */
    protected function getEndpointStatistics(Collection $requests, Collection $responses): array
    {
        $endpoints = [];
        
        foreach ($requests as $request) {
            $endpoint = $request->properties['endpoint'] ?? 'unknown';
            $method = $request->properties['method'] ?? 'unknown';
            $key = "{$method} {$endpoint}";

            if (!isset($endpoints[$key])) {
                $endpoints[$key] = [
                    'count' => 0,
                    'total_time' => 0,
                    'errors' => 0,
                ];
            }

            $endpoints[$key]['count']++;

            $response = $responses->firstWhere('properties.request_id', $request->properties['request_id']);
            if ($response) {
                $endpoints[$key]['total_time'] += $response->properties['response_time'] ?? 0;
                if ($response->properties['status_code'] >= 400) {
                    $endpoints[$key]['errors']++;
                }
            }
        }

        // Calculate averages
        foreach ($endpoints as &$endpoint) {
            $endpoint['average_time'] = $endpoint['count'] > 0 
                ? $endpoint['total_time'] / $endpoint['count'] 
                : 0;
            $endpoint['error_rate'] = $endpoint['count'] > 0 
                ? ($endpoint['errors'] / $endpoint['count']) * 100 
                : 0;
        }

        return $endpoints;
    }

    /**
     * Get rate limit statistics for an IP.
     */
    public function getRateLimitStats(string $ip): array
    {
        $key = "rate_limit_{$ip}";
        
        return Cache::remember($key, 60, function () use ($ip) {
            $requests = activity()
                ->inLog('api_request')
                ->where('properties->ip', $ip)
                ->where('created_at', '>=', now()->subHour())
                ->get();

            return [
                'requests_last_hour' => $requests->count(),
                'requests_last_minute' => $requests->where('created_at', '>=', now()->subMinute())->count(),
                'unique_endpoints' => $requests->pluck('properties.endpoint')->unique()->count(),
            ];
        });
    }
}