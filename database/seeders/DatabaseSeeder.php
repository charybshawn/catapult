<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;

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
            CustomerRoleSeeder::class, // Add customer role and permissions
            FilamentAdminUserSeeder::class,
            CropPlanStatusSeeder::class, // Add crop plan statuses
            PackagingSeeder::class,
            CurrentSeedEntryDataSeeder::class, // Uncomment to seed with actual seed entries
            CurrentSeedConsumableDataSeeder::class, // Uncomment to seed with actual inventory data (run after seed entries)
            // DevelopmentSeeder::class, // Disabled until updated to match simplified schema
        ]);

        // Note: NotificationSetting creation removed as the table structure doesn't match
        // The current notification_settings table is for user-specific settings with channels
    }
}
