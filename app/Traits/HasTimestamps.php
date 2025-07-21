<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasTimestamps
 *
 * Provides enhanced timestamp functionality for models with created_at and updated_at fields.
 * Laravel already provides basic timestamp functionality, so this trait adds additional
 * helpful methods and scopes.
 *
 * @package App\Traits
 */
trait HasTimestamps
{
    /**
     * Scope a query to only include records created today.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCreatedToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope a query to only include records created yesterday.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCreatedYesterday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::yesterday());
    }

    /**
     * Scope a query to only include records created in the last N days.
     *
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeCreatedInLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope a query to only include records updated in the last N days.
     *
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeUpdatedInLastDays(Builder $query, int $days): Builder
    {
        return $query->where('updated_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope a query to only include records created between two dates.
     *
     * @param Builder $query
     * @param mixed $from
     * @param mixed $to
     * @return Builder
     */
    public function scopeCreatedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay()
        ]);
    }

    /**
     * Scope a query to only include records updated between two dates.
     *
     * @param Builder $query
     * @param mixed $from
     * @param mixed $to
     * @return Builder
     */
    public function scopeUpdatedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('updated_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay()
        ]);
    }

    // Note: scopeLatest and scopeOldest are already provided by Laravel's Model class
    // So we don't need to redefine them here

    /**
     * Scope a query to order by last updated date (most recently updated first).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRecentlyUpdated(Builder $query): Builder
    {
        return $query->orderBy('updated_at', 'desc');
    }

    /**
     * Get the age of the record in days.
     *
     * @return int
     */
    public function getAgeInDaysAttribute(): ?int
    {
        return $this->created_at ? $this->created_at->diffInDays(Carbon::now()) : null;
    }

    /**
     * Get the time since last update in a human-readable format.
     *
     * @return string
     */
    public function getTimeSinceUpdateAttribute(): ?string
    {
        return $this->updated_at ? $this->updated_at->diffForHumans() : null;
    }

    /**
     * Get the time since creation in a human-readable format.
     *
     * @return string
     */
    public function getTimeSinceCreationAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    /**
     * Check if the record was created today.
     *
     * @return bool
     */
    public function wasCreatedToday(): bool
    {
        return $this->created_at ? $this->created_at->isToday() : false;
    }

    /**
     * Check if the record was updated today.
     *
     * @return bool
     */
    public function wasUpdatedToday(): bool
    {
        return $this->updated_at ? $this->updated_at->isToday() : false;
    }

    /**
     * Check if the record was created within the last N days.
     *
     * @param int $days
     * @return bool
     */
    public function wasCreatedWithinDays(int $days): bool
    {
        return $this->created_at ? $this->created_at->diffInDays(Carbon::now()) <= $days : false;
    }

    /**
     * Check if the record was updated within the last N days.
     *
     * @param int $days
     * @return bool
     */
    public function wasUpdatedWithinDays(int $days): bool
    {
        return $this->updated_at ? $this->updated_at->diffInDays(Carbon::now()) <= $days : false;
    }

    /**
     * Get formatted creation date.
     *
     * @param string $format
     * @return string
     */
    public function getFormattedCreatedDate(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->created_at ? $this->created_at->format($format) : null;
    }

    /**
     * Get formatted update date.
     *
     * @param string $format
     * @return string
     */
    public function getFormattedUpdatedDate(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->updated_at ? $this->updated_at->format($format) : null;
    }

    /**
     * Touch the model's timestamp quietly without firing events (custom method).
     * Named differently to avoid conflict with Laravel's touchQuietly method.
     *
     * @return bool
     */
    public function touchTimestampsQuietly(): bool
    {
        return $this->timestamps ? $this->update(['updated_at' => Carbon::now()], ['timestamps' => false]) : false;
    }
}