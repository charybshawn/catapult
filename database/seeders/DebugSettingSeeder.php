<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DebugSettingSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Setting::setValue(
            'debug_mode_enabled',
            false,
            'Enable debug information display in admin interface',
            'development'
        );
    }
}
