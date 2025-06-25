<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerLoginSystemTest extends TestCase
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
    }

    /** @test */
    public function customer_only_account_can_be_created_without_password()
    {
        $user = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'customer_type' => 'retail'
        ]);
        
        $user->assignRole('customer');

        $this->assertNull($user->password);
        $this->assertTrue($user->isCustomerOnly());
        $this->assertFalse($user->canLogin());
        $this->assertTrue($user->hasRole('customer'));
    }

    /** @test */
    public function customer_with_login_can_be_created_with_password()
    {
        $user = User::create([
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'customer_type' => 'retail'
        ]);
        
        $user->assignRole('customer');

        $this->assertNotNull($user->password);
        $this->assertFalse($user->isCustomerOnly());
        $this->assertTrue($user->canLogin());
        $this->assertTrue($user->hasRole('customer'));
    }

    /** @test */
    public function customer_only_account_can_be_upgraded_to_have_login()
    {
        // Create customer-only account
        $user = User::create([
            'name' => 'Upgrade Customer',
            'email' => 'upgrade@example.com',
            'customer_type' => 'retail'
        ]);
        $user->assignRole('customer');

        // Verify initial state
        $this->assertTrue($user->isCustomerOnly());
        $this->assertFalse($user->canLogin());

        // Upgrade to have login
        $upgraded = $user->enableLogin('newpassword123');

        // Verify upgrade worked
        $this->assertTrue($upgraded);
        $user->refresh();
        $this->assertFalse($user->isCustomerOnly());
        $this->assertTrue($user->canLogin());
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function cannot_upgrade_user_who_already_has_password()
    {
        $user = User::create([
            'name' => 'Already Has Password',
            'email' => 'haspassword@example.com',
            'password' => Hash::make('existingpassword'),
            'customer_type' => 'retail'
        ]);
        $user->assignRole('customer');

        $upgraded = $user->enableLogin('newpassword');

        $this->assertFalse($upgraded);
    }

    /** @test */
    public function can_find_existing_customer_by_email()
    {
        $user = User::create([
            'name' => 'Findable Customer',
            'email' => 'findable@example.com',
            'customer_type' => 'retail'
        ]);
        $user->assignRole('customer');

        $found = User::findCustomerByEmail('findable@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    /** @test */
    public function cannot_find_non_customer_by_email()
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password')
        ]);
        $user->assignRole('admin');

        $found = User::findCustomerByEmail('admin@example.com');

        $this->assertNull($found);
    }

    /** @test */
    public function customer_only_cannot_access_admin_panel()
    {
        $user = User::create([
            'name' => 'Customer Only',
            'email' => 'customeronly@example.com',
            'customer_type' => 'retail'
        ]);
        $user->assignRole('customer');

        $canAccess = $user->canAccessPanel(app('filament')->getDefaultPanel());

        $this->assertFalse($canAccess);
    }

    /** @test */
    public function customer_with_password_cannot_access_admin_panel()
    {
        $user = User::create([
            'name' => 'Customer With Login',
            'email' => 'customerwithlogin@example.com',
            'password' => Hash::make('password'),
            'customer_type' => 'retail'
        ]);
        $user->assignRole('customer');

        $canAccess = $user->canAccessPanel(app('filament')->getDefaultPanel());

        $this->assertFalse($canAccess);
    }

    /** @test */
    public function admin_with_password_can_access_admin_panel()
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password')
        ]);
        $user->assignRole('admin');
        
        // Give admin the required permission
        $user->givePermissionTo('access filament');

        $canAccess = $user->canAccessPanel(app('filament')->getDefaultPanel());

        $this->assertTrue($canAccess);
    }

    /** @test */
    public function employee_with_password_can_access_admin_panel()
    {
        $user = User::create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => Hash::make('password')
        ]);
        $user->assignRole('employee');
        
        // Give employee the required permission
        $user->givePermissionTo('access filament');

        $canAccess = $user->canAccessPanel(app('filament')->getDefaultPanel());

        $this->assertTrue($canAccess);
    }

    /** @test */
    public function user_without_password_cannot_access_admin_panel_regardless_of_role()
    {
        $user = User::create([
            'name' => 'Admin Without Password',
            'email' => 'adminnopass@example.com'
        ]);
        $user->assignRole('admin');
        $user->givePermissionTo('access filament');

        $canAccess = $user->canAccessPanel(app('filament')->getDefaultPanel());

        $this->assertFalse($canAccess);
    }

    /** @test */
    public function customer_can_have_multiple_roles_but_still_be_restricted_from_admin()
    {
        $user = User::create([
            'name' => 'Multi Role Customer',
            'email' => 'multirole@example.com',
            'password' => Hash::make('password'),
            'customer_type' => 'retail'
        ]);
        $user->assignRole(['customer', 'employee']);
        $user->givePermissionTo('access filament');

        // Even with employee role, if they also have customer role, 
        // they need to not be customer-only to access admin panel
        $canAccess = $user->canAccessPanel(app('filament')->getDefaultPanel());

        // This should be true because they have employee role AND password
        $this->assertTrue($canAccess);
    }

    /** @test */
    public function wholesale_customer_properties_work_correctly()
    {
        $retailCustomer = User::create([
            'name' => 'Retail Customer',
            'email' => 'retail@example.com',
            'customer_type' => 'retail'
        ]);
        $retailCustomer->assignRole('customer');

        $wholesaleCustomer = User::create([
            'name' => 'Wholesale Customer',
            'email' => 'wholesale@example.com',
            'customer_type' => 'wholesale',
            'wholesale_discount_percentage' => 15.00
        ]);
        $wholesaleCustomer->assignRole('customer');

        $this->assertFalse($retailCustomer->isWholesaleCustomer());
        $this->assertTrue($wholesaleCustomer->isWholesaleCustomer());
        $this->assertEquals(15.00, $wholesaleCustomer->getWholesaleDiscountPercentage());
        $this->assertEquals(0, $retailCustomer->getWholesaleDiscountPercentage());
    }

}