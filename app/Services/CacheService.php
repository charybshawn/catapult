<?php

namespace App\Services;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;

class CacheService
{
    protected Repository $cache;
    protected string $prefix;
    protected int $defaultTtl = 3600; // 1 hour

    public function __construct(Repository $cache, string $prefix = 'activity_log')
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    /**
     * Get a cached value
     */
    public function get(string $key, $default = null)
    {
        return $this->cache->get($this->prefixKey($key), $default);
    }

    /**
     * Store a value in cache
     */
    public function put(string $key, $value, int $ttl = null): bool
    {
        return $this->cache->put(
            $this->prefixKey($key),
            $value,
            $ttl ?? $this->defaultTtl
        );
    }

    /**
     * Store a value forever
     */
    public function forever(string $key, $value): bool
    {
        return $this->cache->forever($this->prefixKey($key), $value);
    }

    /**
     * Remove a value from cache
     */
    public function forget(string $key): bool
    {
        return $this->cache->forget($this->prefixKey($key));
    }

    /**
     * Remove all cached values with this prefix
     */
    public function flush(): bool
    {
        // Get all keys with our prefix and delete them
        $keys = $this->cache->get($this->prefix . '_keys', []);
        
        foreach ($keys as $key) {
            $this->cache->forget($key);
        }
        
        $this->cache->forget($this->prefix . '_keys');
        
        return true;
    }

    /**
     * Remember a value in cache
     */
    public function remember(string $key, int $ttl, Closure $callback)
    {
        $prefixedKey = $this->prefixKey($key);
        
        // Track this key
        $this->trackKey($prefixedKey);
        
        return $this->cache->remember($prefixedKey, $ttl ?? $this->defaultTtl, $callback);
    }

    /**
     * Cache user activity metrics
     */
    public function cacheUserMetrics(int $userId, array $metrics, int $ttl = 300): void
    {
        $this->put("user_metrics:{$userId}", $metrics, $ttl);
    }

    /**
     * Get cached user metrics
     */
    public function getUserMetrics(int $userId): ?array
    {
        return $this->get("user_metrics:{$userId}");
    }

    /**
     * Cache model activity count
     */
    public function cacheModelActivityCount(string $model, int $count, int $ttl = 600): void
    {
        $this->put("model_count:" . class_basename($model), $count, $ttl);
    }

    /**
     * Get cached model activity count
     */
    public function getModelActivityCount(string $model): ?int
    {
        return $this->get("model_count:" . class_basename($model));
    }

    /**
     * Cache recent activities
     */
    public function cacheRecentActivities(Collection $activities, int $ttl = 60): void
    {
        $this->put('recent_activities', $activities->toArray(), $ttl);
    }

    /**
     * Get cached recent activities
     */
    public function getRecentActivities(): ?array
    {
        return $this->get('recent_activities');
    }

    /**
     * Cache statistics
     */
    public function cacheStatistics(string $type, array $stats, int $ttl = 1800): void
    {
        $this->put("stats:{$type}", $stats, $ttl);
    }

    /**
     * Get cached statistics
     */
    public function getStatistics(string $type): ?array
    {
        return $this->get("stats:{$type}");
    }

    /**
     * Invalidate user-related caches
     */
    public function invalidateUserCache(int $userId): void
    {
        $this->forget("user_metrics:{$userId}");
        $this->forget("user_activities:{$userId}");
        $this->forget("user_timeline:{$userId}");
    }

    /**
     * Invalidate model-related caches
     */
    public function invalidateModelCache(string $model): void
    {
        $modelName = class_basename($model);
        $this->forget("model_count:{$modelName}");
        $this->forget("model_activities:{$modelName}");
    }

    /**
     * Prefix a cache key
     */
    protected function prefixKey(string $key): string
    {
        return $this->prefix . ':' . $key;
    }

    /**
     * Track a key for bulk operations
     */
    protected function trackKey(string $key): void
    {
        $keys = $this->cache->get($this->prefix . '_keys', []);
        
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            $this->cache->forever($this->prefix . '_keys', $keys);
        }
    }
}