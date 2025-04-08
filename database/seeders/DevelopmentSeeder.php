<?php

namespace Database\Seeders;

use App\Models\Consumable;
use App\Models\Crop;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Recipe;
use App\Models\RecipeMix;
use App\Models\RecipeStage;
use App\Models\SeedVariety;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds for development environment.
     */
    public function run(): void
    {
        // Create test users with different roles
        $customerRole = Role::findByName('customer');
        $employeeRole = Role::findByName('employee');
        
        // Create 5 customers
        User::factory(5)->create()->each(function ($user) use ($customerRole) {
            $user->assignRole($customerRole);
        });
        
        // Create 2 employees
        User::factory(2)->create()->each(function ($user) use ($employeeRole) {
            $user->assignRole($employeeRole);
        });
        
        // Create suppliers (mix of seed, soil, and consumable suppliers)
        $seedSuppliers = Supplier::factory(2)->create(['type' => 'seed']);
        $soilSuppliers = Supplier::factory(2)->create(['type' => 'soil']);
        $consumableSuppliers = Supplier::factory(2)->create(['type' => 'consumable']);
        
        $suppliers = $seedSuppliers->merge($soilSuppliers)->merge($consumableSuppliers);
        
        // Create 10 seed varieties from seed suppliers
        $seedVarieties = SeedVariety::factory(10)
            ->recycle($seedSuppliers)
            ->create();
            
        // Create 5 recipes
        $recipes = Recipe::factory(5)
            ->recycle($soilSuppliers)
            ->recycle($seedVarieties)
            ->create();
            
        // Create recipe stages for each recipe (4 stages per recipe: planting, germination, blackout, light)
        foreach ($recipes as $recipe) {
            RecipeStage::factory(4)
                ->sequence(
                    ['stage' => 'planting', 'notes' => 'Sow seeds evenly'],
                    ['stage' => 'germination', 'notes' => 'Keep moist and warm'],
                    ['stage' => 'blackout', 'notes' => 'Stack trays and cover'],
                    ['stage' => 'light', 'notes' => 'Water twice daily']
                )
                ->for($recipe)
                ->create();
        }
            
        // Create inventory items (mix of soil, seed, and consumables)
        Inventory::factory(3)->seed()->recycle($seedSuppliers)->create();
        Inventory::factory(3)->soil()->recycle($soilSuppliers)->create();
        Inventory::factory(4)->consumable()->recycle($consumableSuppliers)->create();
            
        // Create consumables (mix of packaging, label, and other)
        Consumable::factory()->packaging()->forSupplier($consumableSuppliers->random())->create();
        Consumable::factory()->label()->forSupplier($consumableSuppliers->random())->create();
        Consumable::factory(3)->other()->forSupplier($consumableSuppliers->random())->create();
        
        // Create 10 items for sale
        Item::factory(10)
            ->recycle($recipes)
            ->create();
            
        // Create 15 crops in various stages
        Crop::factory(15)
            ->recycle($recipes)
            ->sequence(
                ['current_stage' => 'planting'],
                ['current_stage' => 'germination'],
                ['current_stage' => 'blackout'],
                ['current_stage' => 'light'],
                ['current_stage' => 'harvested']
            )
            ->create();
            
        $this->command->info('Development data seeded successfully!');
    }
} 