<?php

namespace App\Listeners;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityEventListener
{
    /**
     * Events to ignore
     */
    protected array $ignoredEvents = [
        'eloquent.*',
        'bootstrapping:*',
        'bootstrapped:*',
        'creating:*',
        'created:*',
        'updating:*',
        'updated:*',
        'deleting:*',
        'deleted:*',
        'saving:*',
        'saved:*',
        'restoring:*',
        'restored:*',
        'Illuminate\*',
        'Laravel\*',
        'composing:*',
        'composed:*',
    ];

    /**
     * Events to specifically log
     */
    protected array $logEvents = [
        'login',
        'logout',
        'failed-login',
        'password-reset',
        'verified',
        'registered',
        'lockout',
    ];

    /**
     * Handle the event.
     */
    public function handle(string $eventName, array $data): void
    {
        // Check if we should log this event
        if (!$this->shouldLogEvent($eventName)) {
            return;
        }

        // Get event details
        $eventData = $this->getEventData($eventName, $data);

        if (!$eventData) {
            return;
        }

        // Log the event
        activity()
            ->causedBy($eventData['user'])
            ->withProperties($eventData['properties'])
            ->event($eventData['name'])
            ->log($eventData['description']);
    }

    /**
     * Determine if the event should be logged
     */
    protected function shouldLogEvent(string $eventName): bool
    {
        // Check if it's in our specific log list
        foreach ($this->logEvents as $logEvent) {
            if (Str::contains($eventName, $logEvent)) {
                return true;
            }
        }

        // Check if it should be ignored
        foreach ($this->ignoredEvents as $pattern) {
            if (Str::is($pattern, $eventName)) {
                return false;
            }
        }

        // Check if it's a custom application event
        return Str::startsWith($eventName, 'App\\');
    }

    /**
     * Get event data for logging
     */
    protected function getEventData(string $eventName, array $data): ?array
    {
        // Handle authentication events
        if (Str::contains($eventName, 'Login')) {
            return $this->handleLoginEvent($eventName, $data);
        }

        if (Str::contains($eventName, 'Logout')) {
            return $this->handleLogoutEvent($eventName, $data);
        }

        if (Str::contains($eventName, 'Failed')) {
            return $this->handleFailedLoginEvent($eventName, $data);
        }

        if (Str::contains($eventName, 'Registered')) {
            return $this->handleRegisteredEvent($eventName, $data);
        }

        if (Str::contains($eventName, 'PasswordReset')) {
            return $this->handlePasswordResetEvent($eventName, $data);
        }

        // Handle custom application events
        if (Str::startsWith($eventName, 'App\\Events\\')) {
            return $this->handleCustomEvent($eventName, $data);
        }

        return null;
    }

    /**
     * Handle login event
     */
    protected function handleLoginEvent(string $eventName, array $data): array
    {
        $user = $data['user'] ?? Auth::user();

        return [
            'name' => 'login',
            'description' => 'User logged in',
            'user' => $user,
            'properties' => [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Handle logout event
     */
    protected function handleLogoutEvent(string $eventName, array $data): array
    {
        $user = $data['user'] ?? Auth::user();

        return [
            'name' => 'logout',
            'description' => 'User logged out',
            'user' => $user,
            'properties' => [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Handle failed login event
     */
    protected function handleFailedLoginEvent(string $eventName, array $data): array
    {
        return [
            'name' => 'failed-login',
            'description' => 'Failed login attempt',
            'user' => null,
            'properties' => [
                'credentials' => [
                    'email' => $data['credentials']['email'] ?? 'unknown',
                ],
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Handle registered event
     */
    protected function handleRegisteredEvent(string $eventName, array $data): array
    {
        $user = $data['user'] ?? null;

        return [
            'name' => 'registered',
            'description' => 'New user registered',
            'user' => $user,
            'properties' => [
                'email' => $user?->email,
                'name' => $user?->name,
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Handle password reset event
     */
    protected function handlePasswordResetEvent(string $eventName, array $data): array
    {
        $user = $data['user'] ?? null;

        return [
            'name' => 'password-reset',
            'description' => 'Password was reset',
            'user' => $user,
            'properties' => [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Handle custom application events
     */
    protected function handleCustomEvent(string $eventName, array $data): array
    {
        $eventClass = class_basename($eventName);
        $event = $data[0] ?? null;

        return [
            'name' => Str::snake($eventClass),
            'description' => $this->generateEventDescription($eventClass, $event),
            'user' => Auth::user(),
            'properties' => $this->extractEventProperties($event),
        ];
    }

    /**
     * Generate event description
     */
    protected function generateEventDescription(string $eventClass, $event): string
    {
        // Remove 'Event' suffix if present
        $name = Str::replaceLast('Event', '', $eventClass);
        
        // Convert to human readable
        $name = Str::snake($name, ' ');
        
        return ucfirst($name) . ' occurred';
    }

    /**
     * Extract properties from event object
     */
    protected function extractEventProperties($event): array
    {
        if (!is_object($event)) {
            return [];
        }

        $properties = [];

        // Get public properties
        $reflection = new \ReflectionClass($event);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[$property->getName()] = $property->getValue($event);
        }

        return $properties;
    }
}