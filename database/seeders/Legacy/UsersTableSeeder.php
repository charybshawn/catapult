<?php

namespace Database\Seeders\Legacy;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('users')->truncate();

        // Insert data
        DB::table('users')->insert([
            [
                'id' => 2,
                'name' => 'Admin User',
                'email' => 'charybshawn@gmail.com',
                'phone' => '250-515-4007',
                'customer_type' => 'retail',
                'wholesale_discount_percentage' => 0,
                'company_name' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
                'email_verified_at' => '2025-06-23 02:33:24',
                'password' => '$2y$12$6./eGcGrqjTEByUXoQULduruiccedCDqN92b9uYzb16wQU25K0xoi',
                'remember_token' => null,
                'created_at' => '2025-06-23 02:33:24',
                'updated_at' => '2025-06-23 02:33:24',
            ],
            [
                'id' => 3,
                'name' => 'HANOI 36',
                'email' => 'hanoi36sa@gmail.com',
                'phone' => null,
                'customer_type' => 'retail',
                'wholesale_discount_percentage' => 0,
                'company_name' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
                'email_verified_at' => null,
                'password' => '$2y$12$a.99mIFSyIL/UtCDh7GdRObeP/2h/Olgg2g2LsOllq/xxCGJcue1m',
                'remember_token' => null,
                'created_at' => '2025-06-24 17:59:04',
                'updated_at' => '2025-06-24 17:59:37',
            ],
        ]);
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}