<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Category::create([
            'id' => 1,
            'name' => 'microgreens',
            'description' => 'Microgreens category for fresh produce',
            'is_active' => true,
        ]);
    }
}
