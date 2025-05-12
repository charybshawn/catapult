<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;
use Database\Seeders\RealWorldRecipesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FilamentPermissionSeeder::class,
            RoleSeeder::class,
            FilamentAdminUserSeeder::class,
            MicrogreenSeeder::class,
            PackagingSeeder::class,
            NotificationSettingsSeeder::class,
            RealWorldRecipesSeeder::class,
            // DevelopmentSeeder::class, // Disabled until updated to match simplified schema
        ]);

        // Create crop stage transition notification settings
        NotificationSetting::updateOrCreate(
            [
                'resource_type' => 'crops',
                'event_type' => 'stage_transition',
            ],
            [
                'recipients' => ['admin@example.com'],
                'email_enabled' => true,
                'email_subject_template' => 'Crop #{crop_id} Ready for {stage} Stage',
                'email_body_template' => "Tray {tray_number} of {variety} is ready to be moved to the {stage} stage.\n\nIt has been in the previous stage for {days_in_previous_stage} days.",
                'is_active' => true,
            ]
        );
    }
}
