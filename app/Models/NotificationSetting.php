<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Find a notification setting by resource and event type.
     */
    public static function findByTypeAndEvent(string $resourceType, string $eventType): ?self
    {
        return static::where('resource_type', $resourceType)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Check if email notifications are enabled for this setting.
     */
    public function shouldSendEmail(): bool
    {
        return $this->is_active && $this->email_enabled;
    }
    
    /**
     * Parse a template with provided data.
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
     * Get the email subject with parsed data.
     */
    public function getEmailSubject(array $data): string
    {
        return $this->parseTemplate($this->email_subject_template, $data);
    }
    
    /**
     * Get the email body with parsed data.
     */
    public function getEmailBody(array $data): string
    {
        return $this->parseTemplate($this->email_body_template, $data);
    }
}
