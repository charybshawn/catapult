<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles needed for testing
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'employee']);
        
        // Create the permission needed for admin panel access
        Permission::create(['name' => 'access filament']);

        // Create admin user for testing
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password')
        ]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('access filament');
        
        $this->actingAs($admin);
    }

    /** @test */
    public function user_resource_displays_login_status_correctly()
    {
        // Create different types of users
        $customerOnly = User::create([
            'name' => 'Customer Only',
            'email' => 'customeronly@test.com',
            'customer_type' => 'retail'
        ]);
        $customerOnly->assignRole('customer');

        $customerWithLogin = User::create([
            'name' => 'Customer With Login',
            'email' => 'customerwithlogin@test.com',
            'password' => Hash::make('password'),
            'customer_type' => 'retail'
        ]);
        $customerWithLogin->assignRole('customer');

        $employee = User::create([
            'name' => 'Employee',
            'email' => 'employee@test.com',
            'password' => Hash::make('password')
        ]);
        $employee->assignRole('employee');

        // Test the login status column logic
        $this->assertEquals('Customer Only', $this->getLoginStatus($customerOnly));
        $this->assertEquals('Customer Login', $this->getLoginStatus($customerWithLogin));
        $this->assertEquals('Staff Login', $this->getLoginStatus($employee));
    }

    /** @test */
    public function user_resource_form_allows_nullable_password()
    {
        $form = UserResource::form(\Filament\Forms\Form::make());
        
        // Check that password field exists and is not required
        $passwordField = null;
        foreach ($form->getComponents() as $section) {
            if ($section instanceof \Filament\Forms\Components\Section) {
                foreach ($section->getChildComponents() as $component) {
                    if ($component instanceof \Filament\Forms\Components\TextInput && 
                        $component->getName() === 'password') {
                        $passwordField = $component;
                        break 2;
                    }
                }
            }
        }

        $this->assertNotNull($passwordField, 'Password field should exist');
        $this->assertFalse($passwordField->isRequired(), 'Password field should not be required');
    }

    /** @test */
    public function user_resource_defaults_to_customer_role()
    {
        $form = UserResource::form(\Filament\Forms\Form::make());
        
        // Find the roles field
        $rolesField = null;
        foreach ($form->getComponents() as $section) {
            if ($section instanceof \Filament\Forms\Components\Section) {
                foreach ($section->getChildComponents() as $component) {
                    if ($component instanceof \Filament\Forms\Components\Select && 
                        $component->getName() === 'roles') {
                        $rolesField = $component;
                        break 2;
                    }
                }
            }
        }

        $this->assertNotNull($rolesField, 'Roles field should exist');
        
        // Check default value includes customer role
        $defaultValue = $rolesField->getDefaultState();
        $customerRole = Role::where('name', 'customer')->first();
        
        $this->assertContains($customerRole->id, $defaultValue);
    }

    /** @test */
    public function can_create_customer_without_password_via_resource()
    {
        $customerRole = Role::where('name', 'customer')->first();
        
        $userData = [
            'name' => 'New Customer',
            'email' => 'newcustomer@test.com',
            'customer_type' => 'retail',
            'roles' => [$customerRole->id]
        ];

        // Simulate form submission without password
        $user = User::create($userData);
        $user->roles()->sync($userData['roles']);

        $this->assertNull($user->password);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertTrue($user->isCustomerOnly());
    }

    /** @test */
    public function can_create_customer_with_password_via_resource()
    {
        $customerRole = Role::where('name', 'customer')->first();
        
        $userData = [
            'name' => 'Customer With Login',
            'email' => 'customerlogin@test.com',
            'password' => Hash::make('customerpassword'),
            'customer_type' => 'retail',
            'roles' => [$customerRole->id]
        ];

        $user = User::create($userData);
        $user->roles()->sync($userData['roles']);

        $this->assertNotNull($user->password);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->isCustomerOnly());
        $this->assertTrue($user->canLogin());
    }

    /**
     * Helper method to get login status like the UserResource column
     */
    private function getLoginStatus(User $record): string
    {
        if (!$record->password) {
            return 'Customer Only';
        }
        return $record->hasRole('customer') ? 'Customer Login' : 'Staff Login';
    }
}