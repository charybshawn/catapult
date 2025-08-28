<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Agricultural Application Monitoring Service Provider via Laravel Telescope
 * 
 * Configures Laravel Telescope for monitoring and debugging agricultural production
 * applications, with specific focus on protecting sensitive agricultural business
 * data while enabling comprehensive application performance and error monitoring.
 * 
 * Monitoring Scope:
 * - Local development: Full monitoring for agricultural development workflow
 * - Production environment: Selective monitoring for critical agricultural operations
 * - Exception tracking: Agricultural process failures and error analysis
 * - Performance monitoring: Dashboard and order processing performance analysis
 * - Security protection: Agricultural business data privacy and access control
 * 
 * Agricultural Context:
 * - Production operations require reliable monitoring for customer delivery commitments
 * - Agricultural data contains sensitive business information requiring protection
 * - Performance issues in order processing directly impact customer satisfaction
 * - Crop planning and inventory management systems need error tracking
 * - Development workflows require comprehensive debugging for agricultural business logic
 * 
 * Security Considerations:
 * - Hides sensitive request parameters that might contain agricultural business data
 * - Restricts production access to authorized agricultural management personnel
 * - Filters monitoring data to essential production and error information
 * - Protects CSRF tokens and authentication headers for agricultural system security
 * 
 * @business_domain Agricultural application monitoring and performance analysis
 * @security Agricultural business data protection and access control
 * @development_tools Comprehensive debugging for agricultural business logic
 * 
 * @see Laravel\Telescope\Telescope For core monitoring and debugging functionality
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register agricultural application monitoring services
     * 
     * Configures Telescope monitoring for agricultural production applications
     * with environment-specific filtering and security protections for sensitive
     * agricultural business data.
     * 
     * @return void
     * @business_context Protects agricultural business data while enabling monitoring
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive agricultural request details from being logged by Telescope
     * 
     * Protects sensitive agricultural business data by hiding specific request
     * parameters and headers that might contain confidential production information,
     * customer data, or authentication tokens.
     * 
     * @return void
     * @security Protects agricultural business data and authentication information
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope access gate for agricultural application monitoring
     * 
     * Defines authorization rules for accessing Telescope monitoring data in
     * production environments. This gate protects sensitive agricultural business
     * information and system performance data from unauthorized access.
     * 
     * @return void
     * @security Restricts agricultural monitoring access to authorized personnel
     * @business_context Protects sensitive agricultural production and business data
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
