<?php

namespace Database\Seeders\Legacy;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Create or update admin user
        $admin = User::updateOrCreate(
            ['email' => 'charybshawn@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('kngfqp57'), // Change this in production!
                'email_verified_at' => now(),
                'phone' => '250-515-4007',
            ]
        );
        
        // Assign admin role to user
        $admin->assignRole($adminRole);
        
        $this->command->info('Admin user created: charybshawn@gmail.com');
    }
} 