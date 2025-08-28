<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

/**
 * Authentication session flag listener for agricultural system login tracking.
 * 
 * Sets session-based login flags to track fresh authentication events in the
 * agricultural microgreens management system. Enables UI components and business
 * logic to detect and respond to new login sessions for improved user experience
 * and security monitoring.
 * 
 * @business_domain Authentication workflow for agricultural system access
 * @session_management Login state tracking for agricultural user interface
 * @security_context Fresh login detection for agricultural system security
 */
class SetLoginFlag
{
    /**
     * Handle authentication login event by setting fresh login session flag.
     * 
     * Sets session flag to indicate a fresh login for agricultural system
     * UI components and business logic that need to detect new authentication
     * sessions. Used for welcome messages, security notifications, and workflow routing.
     * 
     * @param Login $event Laravel authentication login event
     * @return void
     * 
     * @session_tracking Sets fresh login indicator for UI and business logic
     * @agricultural_ux Enables login-specific behavior in agricultural interface
     */
    public function handle(Login $event)
    {
        // Set session flag to track fresh login for agricultural system UI/UX
        session(['just_logged_in' => true]);
    }
}