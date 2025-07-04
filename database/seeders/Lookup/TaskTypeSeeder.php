<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaskType;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            // Growing Operations
            ['name' => 'Planting Seeds', 'category' => 'growing', 'sort_order' => 1],
            ['name' => 'Watering', 'category' => 'growing', 'sort_order' => 2],
            ['name' => 'Harvesting', 'category' => 'growing', 'sort_order' => 3],
            ['name' => 'Washing Trays', 'category' => 'growing', 'sort_order' => 4],
            ['name' => 'Making Trays', 'category' => 'growing', 'sort_order' => 5],
            ['name' => 'Making Soil', 'category' => 'growing', 'sort_order' => 6],
            ['name' => 'Seed Soaking', 'category' => 'growing', 'sort_order' => 7],
            ['name' => 'Transplanting', 'category' => 'growing', 'sort_order' => 8],
            ['name' => 'Quality Control', 'category' => 'growing', 'sort_order' => 9],
            
            // Maintenance & Cleaning
            ['name' => 'Cleaning Growing Area', 'category' => 'maintenance', 'sort_order' => 1],
            ['name' => 'Equipment Maintenance', 'category' => 'maintenance', 'sort_order' => 2],
            ['name' => 'Organizing Supplies', 'category' => 'maintenance', 'sort_order' => 3],
            ['name' => 'Sanitizing Equipment', 'category' => 'maintenance', 'sort_order' => 4],
            ['name' => 'Waste Management', 'category' => 'maintenance', 'sort_order' => 5],
            ['name' => 'Temperature Control', 'category' => 'maintenance', 'sort_order' => 6],
            ['name' => 'Humidity Control', 'category' => 'maintenance', 'sort_order' => 7],
            
            // Administrative
            ['name' => 'Computer Work', 'category' => 'administrative', 'sort_order' => 1],
            ['name' => 'Bookkeeping', 'category' => 'administrative', 'sort_order' => 2],
            ['name' => 'Invoicing', 'category' => 'administrative', 'sort_order' => 3],
            ['name' => 'Customer Communication', 'category' => 'administrative', 'sort_order' => 4],
            ['name' => 'Inventory Management', 'category' => 'administrative', 'sort_order' => 5],
            ['name' => 'Order Processing', 'category' => 'administrative', 'sort_order' => 6],
            ['name' => 'Data Entry', 'category' => 'administrative', 'sort_order' => 7],
            ['name' => 'Planning & Scheduling', 'category' => 'administrative', 'sort_order' => 8],
            
            // Packaging & Delivery
            ['name' => 'Packaging Products', 'category' => 'packaging', 'sort_order' => 1],
            ['name' => 'Labeling', 'category' => 'packaging', 'sort_order' => 2],
            ['name' => 'Delivery Preparation', 'category' => 'packaging', 'sort_order' => 3],
            ['name' => 'Delivery', 'category' => 'packaging', 'sort_order' => 4],
            ['name' => 'Loading/Unloading', 'category' => 'packaging', 'sort_order' => 5],
            
            // Other
            ['name' => 'Team Meeting', 'category' => 'other', 'sort_order' => 1],
            ['name' => 'Training', 'category' => 'other', 'sort_order' => 2],
            ['name' => 'Research & Development', 'category' => 'other', 'sort_order' => 3],
            ['name' => 'Supplier Communication', 'category' => 'other', 'sort_order' => 4],
            ['name' => 'Break Time', 'category' => 'other', 'sort_order' => 5],
        ];
        
        foreach ($tasks as $task) {
            TaskType::updateOrCreate(
                ['name' => $task['name']],
                [
                    'category' => $task['category'],
                    'sort_order' => $task['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
