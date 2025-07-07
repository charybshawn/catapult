<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Request;

class AuthenticationEventListener
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        activity('auth_login')
            ->causedBy($event->user)
            ->withProperties([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'remember' => $event->remember,
                'guard' => $event->guard,
                'session_id' => session()->getId(),
                'login_method' => $this->detectLoginMethod(),
            ])
            ->log('User logged in');

        // Update user's last login timestamp
        if (method_exists($event->user, 'updateLastLogin')) {
            $event->user->updateLastLogin();
        }
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            activity('auth_logout')
                ->causedBy($event->user)
                ->withProperties([
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::userAgent(),
                    'guard' => $event->guard,
                    'session_duration' => $this->calculateSessionDuration(),
                ])
                ->log('User logged out');
        }
    }

    /**
     * Handle failed login attempts.
     */
    public function handleFailed(Failed $event): void
    {
        $properties = [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'guard' => $event->guard,
            'attempted_email' => $event->credentials['email'] ?? null,
            'failure_reason' => $this->determineFailureReason($event),
        ];

        activity('auth_failed')
            ->causedBy($event->user)
            ->withProperties($properties)
            ->log('Failed login attempt');

        // Track IP-based failed attempts for security monitoring
        $this->trackFailedAttempt(Request::ip());
    }

    /**
     * Handle account lockout events.
     */
    public function handleLockout(Lockout $event): void
    {
        activity('auth_lockout')
            ->withProperties([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'lockout_duration' => $event->lockoutDuration ?? config('auth.lockout.duration', 60),
            ])
            ->log('Account locked due to too many failed attempts');
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        activity('auth_password_reset')
            ->causedBy($event->user)
            ->withProperties([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'reset_method' => 'email', // Could be extended for other methods
            ])
            ->log('Password was reset');
    }

    /**
     * Handle email verification events.
     */
    public function handleVerified(Verified $event): void
    {
        activity('auth_email_verified')
            ->causedBy($event->user)
            ->withProperties([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'verification_delay' => $event->user->created_at->diffInMinutes(now()),
            ])
            ->log('Email address verified');
    }

    /**
     * Handle user registration events.
     */
    public function handleRegistered(Registered $event): void
    {
        activity('auth_registered')
            ->causedBy($event->user)
            ->withProperties([
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'registration_source' => $this->detectRegistrationSource(),
                'referrer' => Request::header('referer'),
            ])
            ->log('New user registered');
    }

    /**
     * Detect the login method used.
     */
    protected function detectLoginMethod(): string
    {
        if (Request::has('remember')) {
            return 'form';
        }
        
        if (Request::hasHeader('Authorization')) {
            return 'api';
        }
        
        if (Request::is('api/*')) {
            return 'api';
        }

        return 'web';
    }

    /**
     * Calculate session duration.
     */
    protected function calculateSessionDuration(): ?int
    {
        $loginActivity = activity()
            ->causedBy(auth()->user())
            ->inLog('auth_login')
            ->latest()
            ->first();

        if ($loginActivity) {
            return $loginActivity->created_at->diffInSeconds(now());
        }

        return null;
    }

    /**
     * Determine the reason for authentication failure.
     */
    protected function determineFailureReason(Failed $event): string
    {
        if (!$event->user) {
            return 'invalid_credentials';
        }

        if (method_exists($event->user, 'isActive') && !$event->user->isActive()) {
            return 'account_inactive';
        }

        if (method_exists($event->user, 'isSuspended') && $event->user->isSuspended()) {
            return 'account_suspended';
        }

        return 'invalid_password';
    }

    /**
     * Track failed login attempts by IP.
     */
    protected function trackFailedAttempt(string $ip): void
    {
        $key = "failed_attempts_{$ip}";
        $attempts = cache()->get($key, 0) + 1;
        
        cache()->put($key, $attempts, now()->addMinutes(15));

        // Alert if threshold exceeded
        if ($attempts >= config('auth.failed_attempts_threshold', 5)) {
            activity('auth_suspicious_activity')
                ->withProperties([
                    'ip_address' => $ip,
                    'failed_attempts' => $attempts,
                    'period' => '15 minutes',
                ])
                ->log('Suspicious activity detected - multiple failed login attempts');
        }
    }

    /**
     * Detect registration source.
     */
    protected function detectRegistrationSource(): string
    {
        if (Request::is('api/*')) {
            return 'api';
        }

        if (Request::has('oauth_provider')) {
            return 'oauth_' . Request::get('oauth_provider');
        }

        if (Request::has('invitation_code')) {
            return 'invitation';
        }

        return 'web_form';
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
            Verified::class => 'handleVerified',
            Registered::class => 'handleRegistered',
        ];
    }
}