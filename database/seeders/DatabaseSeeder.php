<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;

// Import organized seeders
use Database\Seeders\Core\FilamentPermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\Core\CustomerRoleSeeder;
use Database\Seeders\Core\FilamentAdminUserSeeder;
use Database\Seeders\Lookup\ConsumableTypeSeeder;
use Database\Seeders\Lookup\ConsumableUnitSeeder;
use Database\Seeders\Lookup\CropPlanStatusSeeder;
use Database\Seeders\Lookup\CropStageSeeder;
use Database\Seeders\Lookup\CustomerTypeSeeder;
use Database\Seeders\Lookup\DeliveryStatusSeeder;
use Database\Seeders\Lookup\InventoryReservationStatusSeeder;
use Database\Seeders\Lookup\OrderClassificationSeeder;
use Database\Seeders\Lookup\OrderStatusSeeder;
use Database\Seeders\Lookup\OrderTypeSeeder;
use Database\Seeders\Lookup\PackagingTypeCategorySeeder;
use Database\Seeders\Lookup\PackagingUnitTypeSeeder;
use Database\Seeders\Lookup\PaymentMethodSeeder;
use Database\Seeders\Lookup\PaymentStatusSeeder;
use Database\Seeders\Lookup\ProductStockStatusSeeder;
use Database\Seeders\Lookup\SupplierTypeSeeder;
use Database\Seeders\Lookup\TaskTypeSeeder;
use Database\Seeders\Data\PackagingSeeder;
use Database\Seeders\Data\CurrentSeedEntryDataSeeder;
use Database\Seeders\Data\CurrentSeedConsumableDataSeeder;
use Database\Seeders\DataSeeder;
use Database\Seeders\Development\DevelopmentSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core seeders
            FilamentPermissionSeeder::class,
            RoleSeeder::class,
            CustomerRoleSeeder::class,
            FilamentAdminUserSeeder::class,
            
            // Lookup seeders
            ConsumableTypeSeeder::class,
            ConsumableUnitSeeder::class,
            CropPlanStatusSeeder::class,
            CropStageSeeder::class,
            CustomerTypeSeeder::class,
            DeliveryStatusSeeder::class,
            InventoryReservationStatusSeeder::class,
            OrderClassificationSeeder::class,
            OrderStatusSeeder::class,
            OrderTypeSeeder::class,
            PackagingTypeCategorySeeder::class,
            PackagingUnitTypeSeeder::class,
            PaymentMethodSeeder::class,
            PaymentStatusSeeder::class,
            ProductStockStatusSeeder::class,
            SupplierTypeSeeder::class,
            TaskTypeSeeder::class,
            
            // Data seeders
            PackagingSeeder::class,
            DataSeeder::class,
        ]);

        // Note: NotificationSetting creation removed as the table structure doesn't match
        // The current notification_settings table is for user-specific settings with channels
    }
}
