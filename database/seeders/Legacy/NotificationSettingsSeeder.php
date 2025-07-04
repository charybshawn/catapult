<?php

namespace Database\Seeders\Legacy;

use App\Models\NotificationSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add inventory low stock notification setting
        NotificationSetting::create([
            'resource_type' => 'inventory',
            'event_type' => 'low_stock',
            'recipients' => ['admin@example.com'],
            'email_enabled' => true,
            'email_subject_template' => '[ALERT] {count} Inventory Items Need Restocking',
            'email_body_template' => "The following inventory items are running low and need to be restocked:\n\n" .
                                    "{% for item in items %}" .
                                    "- {item.name}: {item.quantity} {item.unit} remaining (Restock with {item.restock_quantity} {item.unit})\n" .
                                    "{% endfor %}\n\n" .
                                    "Please restock these items as soon as possible.",
            'is_active' => true,
        ]);
        
        // Add inventory out of stock notification setting
        NotificationSetting::create([
            'resource_type' => 'inventory',
            'event_type' => 'out_of_stock',
            'recipients' => ['admin@example.com'],
            'email_enabled' => true,
            'email_subject_template' => '[URGENT] {count} Inventory Items Out of Stock',
            'email_body_template' => "The following inventory items are out of stock and need immediate attention:\n\n" .
                                    "{% for item in items %}" .
                                    "- {item.name} (Recommended restock: {item.restock_quantity})\n" .
                                    "{% endfor %}\n\n" .
                                    "These items require immediate restocking as they are currently unavailable.",
            'is_active' => true,
        ]);
    }
}
