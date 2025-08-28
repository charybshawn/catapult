<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents notification configuration for agricultural system events, enabling
 * automated communication about critical farming operations, harvest alerts, and
 * production milestones. Supports customizable messaging for agricultural workflows.
 *
 * @business_domain Agricultural Communication & Alert Management
 * @workflow_context Used in crop monitoring, harvest scheduling, and production alerts
 * @agricultural_process Automates notifications for farming operations and deadlines
 *
 * Database Table: notification_settings
 * @property int $id Primary identifier for notification setting
 * @property string $resource_type Agricultural resource type (crops, orders, harvests)
 * @property string $event_type Specific event trigger (planted, ready, overdue)
 * @property array $recipients List of notification recipients
 * @property bool $email_enabled Whether email notifications are active
 * @property string|null $email_subject_template Customizable email subject template
 * @property string|null $email_body_template Customizable email body template
 * @property bool $is_active Whether this notification setting is enabled
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @business_rule Templates support dynamic data replacement with {variable} syntax
 * @business_rule Only active settings with email_enabled will send notifications
 * @business_rule Recipients can include multiple email addresses and user roles
 *
 * @agricultural_events Crop stage transitions, harvest readiness, planting schedules
 * @agricultural_alerts Equipment maintenance, seed reordering, quality issues
 */
class NotificationSetting extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'resource_type',
        'event_type',
        'recipients',
        'email_enabled',
        'email_subject_template',
        'email_body_template',
        'is_active',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recipients' => 'json',
        'email_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];
    
    /**
     * Find an active notification setting by resource and event type.
     * Locates specific notification configuration for agricultural events.
     *
     * @param string $resourceType Agricultural resource (crops, orders, harvests)
     * @param string $eventType Specific event trigger (planted, ready, overdue)
     * @return self|null Matching notification setting or null
     * @agricultural_usage Used to find notifications for crop events, harvest alerts
     * @business_rule Only returns active notification settings
     * @example findByTypeAndEvent('crops', 'harvest_ready') for harvest alerts
     */
    public static function findByTypeAndEvent(string $resourceType, string $eventType): ?self
    {
        return static::where('resource_type', $resourceType)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Check if email notifications should be sent for this setting.
     * Validates both setting status and email configuration for agricultural alerts.
     *
     * @return bool True if emails should be sent
     * @agricultural_logic Ensures critical farming notifications are properly configured
     * @business_rule Requires both is_active and email_enabled to be true
     * @usage_context Used before sending crop alerts, harvest notifications
     */
    public function shouldSendEmail(): bool
    {
        return $this->is_active && $this->email_enabled;
    }
    
    /**
     * Parse a template with provided agricultural data.
     * Replaces template variables with actual agricultural context data.
     *
     * @param string $template Template string with {variable} placeholders
     * @param array $data Agricultural data for variable replacement
     * @return string Parsed template with actual values
     * @agricultural_context Supports crop names, dates, quantities, locations
     * @template_variables Common: {crop_name}, {harvest_date}, {variety}, {location}
     * @example "Crop {crop_name} ready for harvest on {harvest_date}"
     */
    public function parseTemplate(string $template, array $data): string
    {
        $result = $template;
        
        foreach ($data as $key => $value) {
            $result = str_replace('{' . $key . '}', $value, $result);
        }
        
        return $result;
    }
    
    /**
     * Get the parsed email subject with agricultural data.
     * Generates contextualized email subject for farming notifications.
     *
     * @param array $data Agricultural context data for template parsing
     * @return string Parsed email subject
     * @agricultural_usage Creates subjects like "Radish Crop Ready for Harvest"
     * @business_usage Used in automated agricultural notification emails
     * @template_context Combines subject template with actual crop/event data
     */
    public function getEmailSubject(array $data): string
    {
        return $this->parseTemplate($this->email_subject_template, $data);
    }
    
    /**
     * Get the parsed email body with agricultural data.
     * Generates full email content for farming operation notifications.
     *
     * @param array $data Agricultural context data for template parsing
     * @return string Parsed email body content
     * @agricultural_usage Creates detailed notifications about farming operations
     * @business_usage Used in automated crop monitoring and alert systems
     * @template_context Includes detailed agricultural information and instructions
     */
    public function getEmailBody(array $data): string
    {
        return $this->parseTemplate($this->email_body_template, $data);
    }
}
