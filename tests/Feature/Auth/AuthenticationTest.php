<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for user authentication in agricultural microgreens management system.
 * 
 * Tests comprehensive authentication functionality including login screen display,
 * user authentication, password validation, and logout functionality for agricultural
 * business users accessing microgreens production and management interfaces.
 *
 * @covers Authentication functionality
 * @group feature
 * @group authentication
 * @group agricultural-testing
 * @group security
 * 
 * @business_context User authentication for agricultural microgreens management system
 * @test_category Feature tests for authentication workflows
 * @agricultural_workflow User access control for microgreens business operations
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login screen rendering for agricultural management system access.
     * 
     * Validates that the login screen loads correctly for agricultural users
     * accessing microgreens management system, ensuring proper interface
     * availability for farm operations and business management.
     *
     * @test
     * @return void
     * @agricultural_scenario Farm user accessing microgreens management system
     * @ui_validation Ensures login interface availability for agricultural users
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Test successful user authentication for agricultural system access.
     * 
     * Validates that agricultural users can successfully authenticate using
     * valid credentials and are redirected to the dashboard for microgreens
     * management system access and agricultural operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Farm user logging into microgreens management system
     * @authentication_workflow Validates successful agricultural user authentication
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * Test authentication failure with invalid password for agricultural system security.
     * 
     * Validates that agricultural users cannot authenticate with incorrect passwords,
     * ensuring security for microgreens management system and protecting sensitive
     * agricultural business data and operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Unauthorized access attempt to agricultural system
     * @security_validation Ensures password protection for agricultural business data
     */
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * Test user logout functionality for agricultural system session management.
     * 
     * Validates that agricultural users can successfully log out of the microgreens
     * management system, properly terminating sessions and ensuring secure access
     * control for agricultural business operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Farm user logging out of microgreens management system
     * @session_management Ensures secure logout for agricultural business users
     */
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
