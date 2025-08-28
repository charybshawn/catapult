<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Agricultural User Profile Update Form Request
 * 
 * Validates profile updates for agricultural management system users.
 * Handles secure validation of name and email changes for farmers,
 * administrators, and agricultural staff while ensuring email uniqueness
 * across the agricultural user base and maintaining data integrity.
 * 
 * @package App\Http\Requests
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see \App\Models\User For agricultural user model and validation
 * @see ProfileController For handling validated profile update requests
 * 
 * @business_context Agricultural user account management and data integrity
 * @validation_features Email uniqueness checking, name format validation
 * @security_features Current user context validation, data sanitization
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Define validation rules for agricultural user profile updates.
     * 
     * Specifies comprehensive validation requirements for agricultural management
     * system user profile modifications. Ensures data integrity and uniqueness
     * constraints while allowing users to update their display name and email
     * address for crop planning, inventory, and order management access.
     * 
     * @return array<string, ValidationRule|array<mixed>|string> Profile validation rules
     * 
     * @validation_rules
     * - name: Required string, max 255 characters for agricultural user display name
     * - email: Required unique email with comprehensive format and uniqueness validation
     * 
     * @email_validation_features
     * - Required field validation
     * - String type enforcement
     * - Automatic lowercase conversion for consistency
     * - Valid email format verification
     * - Maximum length limit (255 characters)
     * - Database uniqueness check excluding current user
     * 
     * @business_context
     * - Agricultural user identity management
     * - Contact information accuracy for order notifications
     * - User account integrity across agricultural operations
     * 
     * @security_features
     * - Current user exclusion from uniqueness check prevents self-blocking
     * - Email normalization with lowercase conversion
     * - Input sanitization and length limits
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
