<?php

namespace App\Traits\Logging;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

trait ExtendedLogsActivity
{
    use LogsActivity, LogsActivityWithRelations;

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults();

        // Automatically capture IP and user agent
        $options->useLogName($this->getLogName())
            ->logOnly($this->getLogAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName));

        return $options;
    }

    /**
     * Get the log name for this model.
     */
    protected function getLogName(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get the attributes that should be logged.
     */
    protected function getLogAttributes(): array
    {
        // Default to all fillable attributes
        return $this->fillable;
    }

    /**
     * Get the description for the given event.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return "This model has been {$eventName}";
    }

    /**
     * Log activity with extended properties.
     */
    public function logExtendedActivity(string $description, array $properties = []): void
    {
        $defaultProperties = [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'request_id' => Request::header('X-Request-ID'),
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ];

        activity($this->getLogName())
            ->performedOn($this)
            ->causedBy(Auth::user())
            ->withProperties(array_merge($defaultProperties, $properties))
            ->log($description);
    }

    /**
     * Log a custom event with metadata.
     */
    public function logCustomEvent(string $event, array $metadata = []): void
    {
        $this->logExtendedActivity("Custom event: {$event}", [
            'event_type' => 'custom',
            'event_name' => $event,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a state transition.
     */
    public function logStateTransition(string $fromState, string $toState, string $field = 'status'): void
    {
        $this->logExtendedActivity("State transition on {$field}", [
            'transition_type' => 'state_change',
            'field' => $field,
            'from_state' => $fromState,
            'to_state' => $toState,
        ]);
    }

    /**
     * Log a relationship change.
     */
    public function logRelationshipChange(string $relation, $relatedModel, string $action): void
    {
        $this->logExtendedActivity("Relationship {$action}: {$relation}", [
            'relationship_type' => 'association',
            'relation' => $relation,
            'related_model' => get_class($relatedModel),
            'related_id' => $relatedModel->getKey(),
            'action' => $action,
        ]);
    }
}