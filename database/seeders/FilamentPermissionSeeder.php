<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FilamentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create the access filament permission
        Permission::firstOrCreate(['name' => 'access filament']);

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Give admin role the access filament permission
        $adminRole->givePermissionTo('access filament');
    }
} 