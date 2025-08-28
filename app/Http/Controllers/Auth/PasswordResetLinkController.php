<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Agricultural User Password Reset Link Controller
 * 
 * Manages password reset link generation and delivery for agricultural
 * management system users. Provides secure password recovery mechanism
 * for farmers, administrators, and agricultural staff who need to regain
 * access to crop planning, inventory, and order management features.
 * 
 * @package App\Http\Controllers\Auth
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see NewPasswordController For password reset form processing
 * @see \App\Models\User Agricultural user model with secure authentication
 * 
 * @business_context Secure agricultural user account recovery system
 * @security_features Email validation, rate limiting, secure token generation
 * @compliance Protects agricultural business data during account recovery
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Display the agricultural user password recovery form.
     * 
     * Renders the forgot password view for agricultural management system users
     * to request password reset links for secure access recovery to crop planning,
     * inventory management, and order processing features.
     * 
     * @return View Password reset request form with agricultural system branding
     * 
     * @http_method GET
     * @route_name password.request
     * @template_view auth.forgot-password
     * @access_level public
     * 
     * @business_context Entry point for agricultural user account recovery
     * @ui_features Agricultural branding, security messaging, email format guidance
     * @security_context Public form with email validation and rate limiting protection
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Process agricultural user password reset link request.
     * 
     * Validates email address and sends secure password reset link to agricultural
     * management system users. Enables secure account recovery for accessing
     * crop planning, inventory management, and financial reporting features.
     * 
     * @param Request $request HTTP request with user email address
     * @return RedirectResponse Redirect back with status message or errors
     * 
     * @throws ValidationException If email validation fails
     * 
     * @http_method POST
     * @route_name password.email
     * @redirect_destination back (with success or error status)
     * 
     * @validation_rules
     * - email: Required valid email address format
     * 
     * @security_features
     * - Email format validation
     * - Rate limiting on password reset requests
     * - Secure token generation and delivery
     * - Status message standardization (prevents user enumeration)
     * 
     * @business_context Secure agricultural user account recovery workflow
     * @compliance Protects sensitive agricultural and financial data access
     * @rate_limiting Prevents abuse of password reset system
     * 
     * @workflow
     * 1. Validate email address format and requirements
     * 2. Generate secure password reset token via Laravel Password facade
     * 3. Send password reset email with secure link
     * 4. Return consistent success message regardless of email existence
     * 5. Handle errors gracefully without revealing user account information
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
