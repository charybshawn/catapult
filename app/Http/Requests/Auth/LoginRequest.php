<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Agricultural User Authentication Form Request
 * 
 * Validates and processes login attempts for agricultural management system users.
 * Implements comprehensive security measures including rate limiting, input validation,
 * and authentication attempt management for farmers, administrators, and agricultural
 * staff accessing crop planning, inventory, and order management features.
 * 
 * @package App\Http\Requests\Auth
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see AuthenticatedSessionController For handling validated login requests
 * @see \App\Models\User For agricultural user authentication model
 * 
 * @business_context Secure agricultural management system access control
 * @security_features Rate limiting, input sanitization, lockout protection
 * @user_types Farmers, agricultural staff, administrators, system users
 */
class LoginRequest extends FormRequest
{
    /**
     * Authorize agricultural user login request.
     * 
     * Determines if the current request is authorized to attempt authentication
     * for the agricultural management system. Always returns true as login
     * attempts are publicly accessible for system access.
     * 
     * @return bool Always true - login attempts are publicly accessible
     * 
     * @authorization_context Public access for agricultural system authentication
     * @security_note Rate limiting and authentication validation handled separately
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define validation rules for agricultural user login requests.
     * 
     * Specifies input validation requirements for agricultural management system
     * authentication. Ensures proper email format and password presence for
     * secure access to crop planning, inventory, and order management features.
     * 
     * @return array<string, ValidationRule|array<mixed>|string> Validation rule array
     * 
     * @validation_rules
     * - email: Required string in valid email format for agricultural user identification
     * - password: Required string for secure agricultural system authentication
     * 
     * @security_context Basic input validation before rate limiting and authentication checks
     * @agricultural_context Validates access credentials for agricultural management features
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Process agricultural user authentication with security controls.
     * 
     * Attempts to authenticate agricultural management system users with comprehensive
     * security measures including rate limiting and login attempt tracking. Manages
     * access to crop planning, inventory management, and order processing features
     * with proper security validation.
     * 
     * @throws ValidationException If authentication fails or rate limit exceeded
     * 
     * @security_workflow
     * 1. Check rate limiting to prevent brute force attacks
     * 2. Attempt authentication with email/password credentials
     * 3. Handle "remember me" functionality for agricultural users
     * 4. Track failed attempts and implement lockout protection
     * 5. Clear rate limiting on successful authentication
     * 
     * @rate_limiting 5 attempts per email/IP combination before lockout
     * @lockout_protection Prevents brute force attacks on agricultural accounts
     * @business_context Secure access control for agricultural business operations
     * @user_experience Supports remember me functionality for trusted devices
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Enforce rate limiting protection for agricultural user login attempts.
     * 
     * Prevents brute force attacks on agricultural management system accounts
     * by implementing rate limiting with lockout functionality. Protects sensitive
     * agricultural and financial data by limiting authentication attempts per
     * email/IP combination.
     * 
     * @throws ValidationException If rate limit exceeded with lockout timing
     * 
     * @rate_limit_rules
     * - Maximum 5 attempts per throttle key (email + IP combination)
     * - Lockout period varies based on attempt frequency
     * - Automatic lockout event firing for security logging
     * 
     * @security_features
     * - IP-based and email-based rate limiting
     * - Lockout event generation for audit trails
     * - User-friendly error messaging with time remaining
     * - Protection against automated attack attempts
     * 
     * @business_context Protects agricultural business data from unauthorized access
     * @compliance Implements security best practices for financial system access
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Generate unique throttle key for agricultural user rate limiting.
     * 
     * Creates a unique identifier combining email address and IP address for
     * rate limiting agricultural management system login attempts. Ensures
     * proper security isolation between different users and locations while
     * preventing circumvention of rate limiting controls.
     * 
     * @return string Unique throttle key for rate limiting identification
     * 
     * @key_composition
     * - Lowercase transliterated email address for consistent formatting
     * - IP address for location-based rate limiting
     * - Pipe separator for key component isolation
     * 
     * @security_features
     * - Email normalization with transliteration for international characters
     * - IP-based tracking to prevent distributed attacks
     * - Consistent key generation for reliable rate limiting
     * 
     * @business_context Protects agricultural user accounts from unauthorized access attempts
     * @example_output "user@farm.com|192.168.1.100"
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
