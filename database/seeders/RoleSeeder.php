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
        $roles = [
            'admin',
            'employee',
            'customer',
        ];
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
            $this->command->info("Role created: {$roleName}");
        }
        
        // In the future, you can add permissions here
        // $permissions = [
        //     'view_dashboard',
        //     'manage_recipes',
        //     'manage_inventory',
        //     // etc.
        // ];
        
        // foreach ($permissions as $permissionName) {
        //     Permission::firstOrCreate(['name' => $permissionName]);
        // }
        
        // Assign permissions to roles
        // $adminRole = Role::findByName('admin');
        // $adminRole->givePermissionTo(Permission::all());
        
        // $employeeRole = Role::findByName('employee');
        // $employeeRole->givePermissionTo([
        //     'view_dashboard',
        //     // etc.
        // ]);
    }
} 