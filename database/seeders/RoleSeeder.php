<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Create permissions
        $manageProducts = Permission::firstOrCreate(['name' => 'manage products']);
        $viewProducts = Permission::firstOrCreate(['name' => 'view products']);
        $editProducts = Permission::firstOrCreate(['name' => 'edit products']);
        $deleteProducts = Permission::firstOrCreate(['name' => 'delete products']);
        $accessFilament = Permission::firstOrCreate(['name' => 'access filament']);

        // Assign permissions to roles
        $admin->givePermissionTo([
            'manage products',
            'view products',
            'edit products',
            'delete products',
            'access filament',
        ]);

        $manager->givePermissionTo([
            'view products',
            'edit products',
            'access filament',
        ]);

        $user->givePermissionTo([
            'view products',
        ]);
    }
} 