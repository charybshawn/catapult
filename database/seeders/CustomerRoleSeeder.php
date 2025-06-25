<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CustomerRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create customer role if it doesn't exist
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        // Create basic permissions for customers (if needed in the future)
        $permissions = [
            'view_own_orders',
            'view_own_profile',
        ];

        foreach ($permissions as $permission) {
            $permissionModel = Permission::firstOrCreate(['name' => $permission]);
            $customerRole->givePermissionTo($permissionModel);
        }

        if ($this->command) {
            $this->command->info('Customer role and permissions created successfully.');
        }
    }
}