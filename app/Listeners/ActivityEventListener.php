<?php

namespace App\Listeners;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * System-wide activity event listener for agricultural application monitoring.
 * 
 * Handles comprehensive event logging for authentication, authorization, and custom
 * application events in agricultural microgreens production management system.
 * Provides security audit trail and business process monitoring with selective
 * event filtering to prevent noise from framework-level events.
 * 
 * @business_domain Activity logging and system monitoring for agricultural operations
 * @security_features Authentication tracking, failed login attempts, audit trails
 * @performance_considerations Selective filtering to avoid overwhelming activity logs
 * @agricultural_context Monitors business events like crop lifecycle, order processing
 */
class ActivityEventListener
{
    /**
     * Framework and system events to ignore during activity logging.
     * 
     * Filters out Laravel/framework noise events to focus on business-relevant
     * activities. Prevents overwhelming activity logs with Eloquent model events,
     * bootstrap events, and other framework internals.
     * 
     * @var array<string> Array of wildcard patterns for events to ignore
     * @business_rule Focus logging on authentication and custom agricultural events
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
     * Authentication and security events to specifically capture.
     * 
     * Critical security events that must be logged for audit trail and
     * compliance in agricultural business management. Includes authentication
     * lifecycle, security violations, and user registration activities.
     * 
     * @var array<string> Priority events for security and compliance logging
     * @security_importance High - required for audit trails and security monitoring
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
     * Main event handler for system-wide activity logging.
     * 
     * Processes Laravel events and determines whether they should be logged
     * based on filtering rules. Creates structured activity log entries with
     * context about user, IP, timestamps, and event-specific data.
     * 
     * @param string $eventName Fully qualified event class name or event identifier
     * @param array $data Event payload data including models, user info, context
     * @return void
     * 
     * @business_process Activity logging workflow for agricultural operations
     * @security_context Captures security-relevant events for audit compliance
     * @performance_note Uses selective filtering to prevent log overwhelming
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
     * Determine if the event should be logged based on filtering rules.
     * 
     * Uses whitelist (logEvents) and blacklist (ignoredEvents) approach to filter
     * events. Prioritizes authentication/security events, allows custom application
     * events, and blocks noisy framework events.
     * 
     * @param string $eventName Event name to evaluate for logging
     * @return bool True if event should be logged, false to skip
     * 
     * @business_rule Authentication events always logged, framework events ignored
     * @performance_optimization Prevents overwhelming logs with framework noise
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
     * Extract and structure event data for activity logging.
     * 
     * Transforms raw event data into standardized format for activity logs.
     * Handles different event types with specific extraction logic for 
     * authentication events, custom application events, and security events.
     * 
     * @param string $eventName Event identifier for routing to specific handlers
     * @param array $data Raw event payload data
     * @return array|null Structured event data or null if not loggable
     * 
     * @return_format ['name' => string, 'description' => string, 'user' => User|null, 'properties' => array]
     * @business_context Captures agricultural business events with relevant context
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
     * Handle user authentication login events with security context.
     * 
     * Captures successful login attempts with security-relevant information
     * including IP address, user agent, and timestamp for audit trail.
     * Essential for security monitoring in agricultural business system.
     * 
     * @param string $eventName Login event identifier
     * @param array $data Event data containing user information
     * @return array Structured login event data for activity logging
     * 
     * @security_importance Critical for authentication audit trails
     * @compliance_requirement Tracks user access for security compliance
     * @agricultural_context Monitors farm operation system access
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
     * Handle user logout events with session termination context.
     * 
     * Records user session termination for security audit and session
     * management tracking. Important for monitoring agricultural system
     * access patterns and detecting unusual logout behaviors.
     * 
     * @param string $eventName Logout event identifier
     * @param array $data Event data containing user and session information
     * @return array Structured logout event data for activity logging
     * 
     * @security_feature Session termination tracking for audit compliance
     * @business_monitoring Tracks agricultural system usage patterns
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
     * Handle failed authentication attempts with security alerting.
     * 
     * Captures failed login attempts for security monitoring and potential
     * intrusion detection. Records attempted credentials (email only) and
     * source information without logging sensitive password data.
     * 
     * @param string $eventName Failed login event identifier
     * @param array $data Event data containing attempted credentials and context
     * @return array Structured failed login event data for security monitoring
     * 
     * @security_critical Essential for intrusion detection and security monitoring
     * @privacy_compliant Logs email but never passwords for security analysis
     * @agricultural_security Protects agricultural business data from unauthorized access
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
     * Handle new user registration events for account lifecycle tracking.
     * 
     * Records new user account creation with basic identifying information
     * for user lifecycle management and system growth monitoring in
     * agricultural business operations.
     * 
     * @param string $eventName Registration event identifier
     * @param array $data Event data containing new user information
     * @return array Structured registration event data for user lifecycle tracking
     * 
     * @business_process User onboarding workflow for agricultural system access
     * @compliance_tracking Account creation audit trail for business records
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
     * Handle password reset events for security audit compliance.
     * 
     * Records password reset operations for security monitoring and
     * audit compliance. Critical for tracking potential security incidents
     * and user account recovery in agricultural business system.
     * 
     * @param string $eventName Password reset event identifier
     * @param array $data Event data containing user and reset context
     * @return array Structured password reset event data for security audit
     * 
     * @security_audit Critical security event requiring detailed logging
     * @compliance_requirement Password change tracking for security compliance
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
     * Handle custom agricultural application events with business context.
     * 
     * Processes custom business events specific to agricultural operations
     * such as crop lifecycle events, order processing, harvest completion,
     * and other domain-specific business activities.
     * 
     * @param string $eventName Custom application event class name
     * @param array $data Event payload containing business domain objects
     * @return array Structured custom event data for business activity logging
     * 
     * @business_domain Agricultural operations including crops, orders, harvests
     * @event_types CropPlanted, OrderHarvested, OrderPacked, AllCropsReady
     * @audit_trail Comprehensive business process tracking for agricultural operations
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
     * Generate human-readable event descriptions for activity logs.
     * 
     * Converts technical event class names to readable descriptions for
     * activity log entries. Handles agricultural business event naming
     * conventions and provides meaningful descriptions for audit trails.
     * 
     * @param string $eventClass Event class name without namespace
     * @param mixed $event Event object instance for context
     * @return string Human-readable event description
     * 
     * @example 'OrderCropPlantedEvent' becomes 'Order crop planted occurred'
     * @business_context Provides meaningful descriptions for agricultural events
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
     * Extract public properties from event objects for activity logging.
     * 
     * Uses reflection to extract all public properties from event objects
     * to capture relevant business context in activity logs. Safely handles
     * agricultural domain events with complex object properties.
     * 
     * @param mixed $event Event object to extract properties from
     * @return array Associative array of property names and values
     * 
     * @reflection_use Uses PHP reflection to access public properties safely
     * @business_context Captures agricultural event data like crop IDs, order details
     * @error_handling Returns empty array if event is not an object
     */
    protected function extractEventProperties($event): array
    {
        if (!is_object($event)) {
            return [];
        }

        $properties = [];

        // Get public properties
        $reflection = new ReflectionClass($event);
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[$property->getName()] = $property->getValue($event);
        }

        return $properties;
    }
}