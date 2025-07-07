<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Logging\ExtendedLogsActivity;

class ActivityLogObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        // Skip if model already uses LogsActivity or ExtendedLogsActivity trait
        if ($this->usesActivityLogging($model)) {
            return;
        }

        activity()
            ->performedOn($model)
            ->event('created')
            ->withProperties($this->getModelProperties($model))
            ->log($this->getDescription($model, 'created'));
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Skip if model already uses LogsActivity or ExtendedLogsActivity trait
        if ($this->usesActivityLogging($model)) {
            return;
        }

        $properties = [
            'old' => $model->getOriginal(),
            'attributes' => $model->getChanges(),
        ];

        activity()
            ->performedOn($model)
            ->event('updated')
            ->withProperties($properties)
            ->log($this->getDescription($model, 'updated'));
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        // Skip if model already uses LogsActivity or ExtendedLogsActivity trait
        if ($this->usesActivityLogging($model)) {
            return;
        }

        activity()
            ->performedOn($model)
            ->event('deleted')
            ->withProperties($this->getModelProperties($model))
            ->log($this->getDescription($model, 'deleted'));
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        // Skip if model already uses LogsActivity or ExtendedLogsActivity trait
        if ($this->usesActivityLogging($model)) {
            return;
        }

        activity()
            ->performedOn($model)
            ->event('restored')
            ->withProperties($this->getModelProperties($model))
            ->log($this->getDescription($model, 'restored'));
    }

    /**
     * Check if model uses activity logging traits
     */
    protected function usesActivityLogging(Model $model): bool
    {
        return in_array(LogsActivity::class, class_uses_recursive($model)) ||
               in_array(ExtendedLogsActivity::class, class_uses_recursive($model));
    }

    /**
     * Get properties to log for the model
     */
    protected function getModelProperties(Model $model): array
    {
        $properties = $model->toArray();

        // Remove sensitive fields
        $hidden = array_merge(
            $model->getHidden(),
            ['password', 'remember_token', 'api_token', 'secret', 'token']
        );

        foreach ($hidden as $field) {
            unset($properties[$field]);
        }

        return $properties;
    }

    /**
     * Generate description for the activity
     */
    protected function getDescription(Model $model, string $event): string
    {
        $modelName = class_basename($model);
        $key = $model->getKey();

        return match($event) {
            'created' => "{$modelName} (#{$key}) was created",
            'updated' => "{$modelName} (#{$key}) was updated",
            'deleted' => "{$modelName} (#{$key}) was deleted",
            'restored' => "{$modelName} (#{$key}) was restored",
            default => "{$modelName} (#{$key}) {$event}",
        };
    }
}