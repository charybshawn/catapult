<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Agricultural User Registration Controller
 * 
 * Manages new user registration for the agricultural management system.
 * Handles account creation for farmers, agricultural staff, and system
 * administrators who need access to crop planning, inventory management,
 * and order processing features.
 * 
 * @package App\Http\Controllers\Auth
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see \App\Models\User Agricultural user model with roles and permissions
 * @see AuthenticatedSessionController For post-registration authentication
 * 
 * @business_context Agricultural user onboarding and account management
 * @security_features Password strength validation, email uniqueness, secure hashing
 * @user_types Farmers, agricultural staff, administrators, system users
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the agricultural user registration form.
     * 
     * Renders the user registration view for new agricultural management system
     * users to create accounts for accessing crop planning, inventory management,
     * order processing, and financial reporting features.
     * 
     * @return View Registration form with agricultural system branding and context
     * 
     * @http_method GET
     * @route_name register
     * @template_view auth.register
     * @access_level public
     * 
     * @business_context New agricultural user onboarding entry point
     * @ui_features Agricultural branding, role information, security requirements
     * @registration_context Form for farmers, staff, and administrators
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Process agricultural user registration request.
     * 
     * Validates and creates new agricultural management system user accounts.
     * Handles secure account creation with password hashing, email verification
     * setup, and automatic login to agricultural dashboard for immediate access
     * to crop planning and inventory management features.
     * 
     * @param Request $request HTTP request with registration data
     * @return RedirectResponse Redirect to agricultural dashboard after successful registration
     * 
     * @throws ValidationException If registration validation fails
     * 
     * @http_method POST
     * @route_name register.store
     * @redirect_success dashboard (agricultural management interface)
     * @redirect_failure back (with validation errors)
     * 
     * @validation_rules
     * - name: Required string, max 255 characters (agricultural user display name)
     * - email: Required unique email, lowercase, max 255 characters
     * - password: Required confirmed password meeting Laravel security defaults
     * 
     * @security_features
     * - Email uniqueness validation across agricultural user base
     * - Secure password hashing with Laravel defaults
     * - Automatic user authentication post-registration
     * - Registration event firing for audit trails
     * 
     * @business_context Agricultural user onboarding and immediate system access
     * @user_experience Seamless registration to dashboard flow for new farmers/staff
     * @compliance Secure account creation for agricultural business data access
     * 
     * @workflow
     * 1. Validate user registration data including email uniqueness
     * 2. Create new User record with secure password hashing
     * 3. Fire Registered event for email verification and audit logging
     * 4. Automatically authenticate new user
     * 5. Redirect to agricultural management dashboard
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
