<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLogApiRequest extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log_api_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'activity_log_id',
        'endpoint',
        'method',
        'api_version',
        'client_id',
        'api_key_id',
        'request_headers',
        'request_body',
        'query_parameters',
        'response_status',
        'response_headers',
        'response_body',
        'response_time_ms',
        'response_size_bytes',
        'ip_address',
        'user_agent',
        'is_authenticated',
        'user_id',
        'error_message',
        'rate_limit_info',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'query_parameters' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'rate_limit_info' => 'array',
        'response_time_ms' => 'float',
        'response_size_bytes' => 'integer',
        'is_authenticated' => 'boolean',
    ];

    /**
     * Get the activity log that owns the API request.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_log_id');
    }

    /**
     * Get the user associated with the API request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by endpoint.
     */
    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope to filter by HTTP method.
     */
    public function scopeForMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope to filter by response status.
     */
    public function scopeWithStatus($query, int $status)
    {
        return $query->where('response_status', $status);
    }

    /**
     * Scope to filter successful requests (2xx status codes).
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    /**
     * Scope to filter failed requests (4xx and 5xx status codes).
     */
    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Scope to filter slow API requests.
     */
    public function scopeSlow($query, float $thresholdMs = 1000)
    {
        return $query->where('response_time_ms', '>', $thresholdMs);
    }

    /**
     * Scope to filter authenticated requests.
     */
    public function scopeAuthenticated($query)
    {
        return $query->where('is_authenticated', true);
    }

    /**
     * Check if the request was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if the request failed.
     */
    public function isFailed(): bool
    {
        return $this->response_status >= 400;
    }

    /**
     * Check if the request was a client error.
     */
    public function isClientError(): bool
    {
        return $this->response_status >= 400 && $this->response_status < 500;
    }

    /**
     * Check if the request was a server error.
     */
    public function isServerError(): bool
    {
        return $this->response_status >= 500;
    }

    /**
     * Get the response status category.
     */
    public function getStatusCategoryAttribute(): string
    {
        return match (true) {
            $this->response_status < 200 => 'informational',
            $this->response_status < 300 => 'successful',
            $this->response_status < 400 => 'redirection',
            $this->response_status < 500 => 'client_error',
            default => 'server_error',
        };
    }

    /**
     * Get the formatted endpoint with method.
     */
    public function getFormattedEndpointAttribute(): string
    {
        return "{$this->method} {$this->endpoint}";
    }

    /**
     * Check if rate limit was hit.
     */
    public function wasRateLimited(): bool
    {
        return $this->response_status === 429;
    }

    /**
     * Get the response size in a human-readable format.
     */
    public function getFormattedResponseSizeAttribute(): string
    {
        $bytes = $this->response_size_bytes;
        
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        
        $units = ['KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor + 1), $units[$factor]);
    }
}