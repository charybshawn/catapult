<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural User Authentication Session Controller
 * 
 * Manages authentication sessions for agricultural management system users.
 * Handles login/logout workflows for farmers, administrators, and agricultural
 * staff accessing crop planning, inventory, and order management features.
 * 
 * @package App\Http\Controllers\Auth
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see LoginRequest For authentication request validation and rate limiting
 * @see \App\Models\User For agricultural user model with roles and permissions
 * 
 * @business_context Agricultural user management with role-based access control
 * @security_features Rate limiting, session regeneration, comprehensive audit logging
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the agricultural management system login view.
     * 
     * Renders the authentication form for agricultural users to access
     * crop planning, inventory management, and order processing features.
     * 
     * @return View Login form view with agricultural branding and context
     * 
     * @http_method GET
     * @route_name login
     * @template_view auth.login
     * @access_level public
     * 
     * @business_context Entry point for agricultural management system access
     * @ui_features Agricultural branding, role-based messaging, security notices
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Process agricultural user authentication request.
     * 
     * Authenticates agricultural management system users and redirects to
     * the dashboard for crop planning, inventory, and order management.
     * Includes comprehensive security logging for agricultural business auditing.
     * 
     * @param LoginRequest $request Validated login request with rate limiting
     * @return RedirectResponse Redirect to intended page or agricultural dashboard
     * 
     * @throws ValidationException If authentication credentials are invalid
     * @throws Exception If authentication process encounters system errors
     * 
     * @http_method POST
     * @route_name login.store
     * @redirect_success dashboard (agricultural management interface)
     * @redirect_failure login (with validation errors)
     * 
     * @security_features Rate limiting, session regeneration, audit logging
     * @business_context Secure access to agricultural management operations
     * @audit_logging Records all authentication attempts with IP and user agent
     * 
     * @workflow
     * 1. Log authentication attempt with security context
     * 2. Validate credentials through LoginRequest authentication
     * 3. Regenerate session for security
     * 4. Redirect to agricultural dashboard or intended agricultural page
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        try {
            $request->authenticate();
            Log::info('Authentication successful');
        } catch (Exception $e) {
            Log::error('Authentication failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Terminate agricultural user authentication session.
     * 
     * Securely logs out agricultural users from the management system,
     * invalidating sessions and clearing authentication tokens to protect
     * sensitive agricultural and business data.
     * 
     * @param Request $request HTTP request with session data
     * @return RedirectResponse Redirect to public homepage
     * 
     * @http_method POST
     * @route_name logout
     * @redirect_destination / (public homepage)
     * 
     * @security_features Session invalidation, token regeneration, guard logout
     * @business_context Secure agricultural data protection on logout
     * @compliance Ensures sensitive crop and financial data is properly secured
     * 
     * @workflow
     * 1. Log out user from web authentication guard
     * 2. Invalidate entire session data
     * 3. Regenerate CSRF token for security
     * 4. Redirect to public homepage
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
