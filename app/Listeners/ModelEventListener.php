<?php

namespace App\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ModelEventListener
{
    /**
     * Models to exclude from logging.
     */
    protected array $excludedModels = [
        \Spatie\Activitylog\Models\Activity::class,
    ];

    /**
     * Handle model retrieved events.
     */
    public function retrieved(Model $model): void
    {
        if ($this->shouldLog($model, 'retrieved')) {
            activity('model_retrieved')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'attributes_loaded' => array_keys($model->getAttributes()),
                    'relations_loaded' => array_keys($model->getRelations()),
                    'accessed_from' => Request::path(),
                ])
                ->log('Model retrieved');
        }
    }

    /**
     * Handle model creating events.
     */
    public function creating(Model $model): void
    {
        if ($this->shouldLog($model, 'creating')) {
            activity('model_creating')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'attributes' => $model->getAttributes(),
                    'creating_from' => Request::path(),
                ])
                ->log('Model being created');
        }
    }

    /**
     * Handle model updating events.
     */
    public function updating(Model $model): void
    {
        if ($this->shouldLog($model, 'updating')) {
            activity('model_updating')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'original' => $model->getOriginal(),
                    'changes' => $model->getDirty(),
                    'updating_from' => Request::path(),
                ])
                ->log('Model being updated');
        }
    }

    /**
     * Handle model saving events.
     */
    public function saving(Model $model): void
    {
        if ($this->shouldLog($model, 'saving')) {
            $isCreating = !$model->exists;
            
            activity('model_saving')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'is_creating' => $isCreating,
                    'attributes' => $model->getAttributes(),
                    'dirty' => $model->getDirty(),
                ])
                ->log($isCreating ? 'Model being saved (new)' : 'Model being saved (existing)');
        }
    }

    /**
     * Handle model deleting events.
     */
    public function deleting(Model $model): void
    {
        if ($this->shouldLog($model, 'deleting')) {
            activity('model_deleting')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'attributes' => $model->getAttributes(),
                    'is_soft_delete' => method_exists($model, 'trashed'),
                    'deleting_from' => Request::path(),
                ])
                ->log('Model being deleted');
        }
    }

    /**
     * Handle model restored events.
     */
    public function restored(Model $model): void
    {
        if ($this->shouldLog($model, 'restored')) {
            activity('model_restored')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'restored_from' => Request::path(),
                ])
                ->log('Model restored from soft delete');
        }
    }

    /**
     * Handle model force deleted events.
     */
    public function forceDeleted(Model $model): void
    {
        if ($this->shouldLog($model, 'forceDeleted')) {
            activity('model_force_deleted')
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'attributes' => $model->getAttributes(),
                    'force_deleted_from' => Request::path(),
                ])
                ->log('Model permanently deleted');
        }
    }

    /**
     * Handle model replicating events.
     */
    public function replicating(Model $model): void
    {
        if ($this->shouldLog($model, 'replicating')) {
            activity('model_replicating')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'original_id' => $model->getKey(),
                    'attributes' => $model->getAttributes(),
                ])
                ->log('Model being replicated');
        }
    }

    /**
     * Determine if the model event should be logged.
     */
    protected function shouldLog(Model $model, string $event): bool
    {
        // Skip excluded models
        if (in_array(get_class($model), $this->excludedModels)) {
            return false;
        }

        // Skip if model explicitly disables logging
        if (property_exists($model, 'logModelEvents') && $model->logModelEvents === false) {
            return false;
        }

        // Skip specific events if configured
        if (property_exists($model, 'dontLogEvents') && in_array($event, $model->dontLogEvents)) {
            return false;
        }

        // Check if only specific events should be logged
        if (property_exists($model, 'onlyLogEvents') && !in_array($event, $model->onlyLogEvents)) {
            return false;
        }

        // Check global configuration
        $enabledEvents = config('logging.model_events', ['creating', 'updating', 'deleting']);
        return in_array($event, $enabledEvents);
    }

    /**
     * Register model event listeners.
     */
    public static function registerModelListeners(): void
    {
        $events = [
            'retrieved',
            'creating',
            'created',
            'updating',
            'updated',
            'saving',
            'saved',
            'deleting',
            'deleted',
            'restoring',
            'restored',
            'replicating',
        ];

        foreach ($events as $event) {
            \Illuminate\Database\Eloquent\Model::$event(function (Model $model) use ($event) {
                app(static::class)->$event($model);
            });
        }
    }
}