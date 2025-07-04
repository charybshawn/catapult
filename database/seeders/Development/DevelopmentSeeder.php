<?php

namespace Database\Seeders\Development;

use App\Models\Consumable;
use App\Models\Crop;
use App\Models\Item;
use App\Models\Recipe;
use App\Models\RecipeMix;
use App\Models\RecipeStage;
use App\Models\SeedCultivar;
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
        
        // Get existing seed cultivars
        $seedCultivars = SeedCultivar::all();
            
        // Create 5 recipes
        $recipes = Recipe::factory(5)
            ->recycle($seedCultivars)
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