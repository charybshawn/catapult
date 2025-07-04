<?php

namespace Database\Seeders\Core;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FilamentAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Create a persistent Filament admin user
        $filamentAdmin = User::firstOrCreate(
            ['email' => 'charybshawn@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('kngfqp57'), // Simple password for testing
                'email_verified_at' => now(),
                'phone' => '250-000-0000',
            ]
        );
        
        // Update password for existing user
        if (!$filamentAdmin->wasRecentlyCreated) {
            $filamentAdmin->password = Hash::make('password');
            $filamentAdmin->save();
        }
        
        // Assign admin role to user
        $filamentAdmin->assignRole($adminRole);
        
        $this->command->info('Filament admin user created/updated: charybshawn@gmail.com');
    }
} 