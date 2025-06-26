<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelHasRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('model_has_roles')->truncate();

        // Insert data
        DB::table('model_has_roles')->insert([
            [
                'role_id' => 1,
                'model_type' => 'App\\\\Models\\\\User',
                'model_id' => 2,
            ],
        ]);
    }
}