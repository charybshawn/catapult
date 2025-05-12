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
        $admin = Role::create(['name' => 'admin']);
        $manager = Role::create(['name' => 'manager']);
        $user = Role::create(['name' => 'user']);

        // Create permissions
        $manageProducts = Permission::create(['name' => 'manage products']);
        $viewProducts = Permission::create(['name' => 'view products']);
        $editProducts = Permission::create(['name' => 'edit products']);
        $deleteProducts = Permission::create(['name' => 'delete products']);
        $accessFilament = Permission::create(['name' => 'access filament']);

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