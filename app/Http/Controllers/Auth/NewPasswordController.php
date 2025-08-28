<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Agricultural User Password Reset Controller
 * 
 * Manages secure password reset functionality for agricultural management
 * system users. Handles the password reset form display and processing
 * for farmers, administrators, and agricultural staff.
 * 
 * @package App\Http\Controllers\Auth
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see \App\Models\User Agricultural user model with secure authentication
 * @see PasswordResetLinkController For password reset link generation
 * 
 * @business_context Secure agricultural user account management
 * @security_features Token validation, secure password hashing, event logging
 * @compliance Ensures agricultural business data remains secure during password resets
 */
class NewPasswordController extends Controller
{
    /**
     * Display the agricultural user password reset form.
     * 
     * Renders the password reset view for agricultural management system users
     * to securely update their passwords when accessing crop planning, inventory,
     * and order management features.
     * 
     * @param Request $request HTTP request containing password reset token
     * @return View Password reset form with agricultural system branding
     * 
     * @http_method GET
     * @route_name password.reset
     * @template_view auth.reset-password
     * @required_params token (password reset token)
     * 
     * @business_context Secure access recovery for agricultural management users
     * @security_features Token-based password reset with form validation
     * @ui_features Agricultural branding, security messaging, password strength indicators
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Process agricultural user password reset request.
     * 
     * Validates and processes password reset for agricultural management system users,
     * ensuring secure access to crop planning, inventory management, and order processing
     * features. Implements secure password hashing and remember token regeneration.
     * 
     * @param Request $request HTTP request with password reset data
     * @return RedirectResponse Redirect to login on success or back with errors
     * 
     * @throws ValidationException If password reset validation fails
     * 
     * @http_method POST
     * @route_name password.store
     * @redirect_success login (with success message)
     * @redirect_failure back (with validation errors)
     * 
     * @validation_rules
     * - token: Required password reset token
     * - email: Required valid email address
     * - password: Required, confirmed, meets Laravel password defaults
     * 
     * @security_features
     * - Token-based password reset validation
     * - Secure password hashing with Laravel defaults
     * - Remember token regeneration for session security
     * - Password reset event logging
     * 
     * @business_context Secure agricultural user account recovery
     * @compliance Protects sensitive agricultural and financial data access
     * 
     * @workflow
     * 1. Validate password reset token and new password requirements
     * 2. Attempt password reset through Laravel's Password facade
     * 3. Update user password with secure hashing
     * 4. Regenerate remember token for enhanced security
     * 5. Fire PasswordReset event for audit logging
     * 6. Redirect to login with success or return with errors
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
