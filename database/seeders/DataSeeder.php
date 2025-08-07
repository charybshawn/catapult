<?php

namespace Database\Seeders;

use Database\Seeders\Data\CategoriesTableSeeder;
use Database\Seeders\Data\CurrentSeedConsumableDataSeeder;
use Database\Seeders\Data\CustomerSeeder;
use Database\Seeders\Data\MasterCultivarsTableSeeder;
use Database\Seeders\Data\MasterSeedCatalogTableSeeder;
use Database\Seeders\Data\PackagingSeeder;
use Database\Seeders\Data\PackagingTypesTableSeeder;
use Database\Seeders\Data\PriceVariationsSeeder;
use Database\Seeders\Data\ProductMixesTableSeeder;
use Database\Seeders\Data\ProductsTableSeeder;
use Database\Seeders\Data\RecipesTableSeeder;
use Database\Seeders\Data\SuppliersSeeder;
use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    /**
     * Run all data seeders.
     */
    public function run(): void
    {
        $this->call([
            CategoriesTableSeeder::class,
            CurrentSeedConsumableDataSeeder::class,
            CustomerSeeder::class,
            MasterCultivarsTableSeeder::class,
            MasterSeedCatalogTableSeeder::class,
            PackagingSeeder::class,
            PackagingTypesTableSeeder::class,
            ProductMixesTableSeeder::class,
            ProductsTableSeeder::class,
            PriceVariationsSeeder::class,
            RecipesTableSeeder::class,
            SuppliersSeeder::class,
        ]);
    }
}
