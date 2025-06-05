<?php

namespace App\Filament\Pages\Base;

use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class BaseEditRecord extends EditRecord
{
    /**
     * Flag to track if we've sent a custom notification
     */
    protected bool $customNotificationSent = false;
    
    /**
     * Global override to redirect back to list page after save
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    /**
     * Override to prevent default notification when we send custom ones
     */
    protected function getSavedNotification(): ?Notification
    {
        if ($this->customNotificationSent) {
            return null;
        }
        
        return parent::getSavedNotification();
    }
    
    /**
     * Helper method to send custom notification and suppress default
     */
    protected function sendCustomNotification(Notification $notification): void
    {
        $notification->send();
        $this->customNotificationSent = true;
    }
}