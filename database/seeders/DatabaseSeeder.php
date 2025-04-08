<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call role seeder first to ensure roles exist before creating users
        $this->call(RoleSeeder::class);
        
        // Call admin user seeders to create persistent admin users
        $this->call(FilamentAdminUserSeeder::class);
        
        // Only seed development data if we're in a development environment
        if (app()->environment('local', 'development')) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
