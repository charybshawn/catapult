<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Data\CurrentSeedConsumableDataSeeder;
use Database\Seeders\Data\CustomerSeeder;
use Database\Seeders\Data\MasterCultivarsTableSeeder;
use Database\Seeders\Data\MasterSeedCatalogTableSeeder;
use Database\Seeders\Data\PackagingSeeder;
use Database\Seeders\Data\PackagingTypesTableSeeder;
use Database\Seeders\Data\PriceVariationsTableSeeder;
use Database\Seeders\Data\ProductMixesTableSeeder;
use Database\Seeders\Data\ProductsTableSeeder;
use Database\Seeders\Data\RecipesTableSeeder;
use Database\Seeders\Data\SuppliersSeeder;

class DataSeeder extends Seeder
{
    /**
     * Run all data seeders.
     */
    public function run(): void
    {
        $this->call([
            CurrentSeedConsumableDataSeeder::class,
            CustomerSeeder::class,
            MasterCultivarsTableSeeder::class,
            MasterSeedCatalogTableSeeder::class,
            PackagingSeeder::class,
            PackagingTypesTableSeeder::class,
            PriceVariationsTableSeeder::class,
            ProductMixesTableSeeder::class,
            ProductsTableSeeder::class,
            RecipesTableSeeder::class,
            SuppliersSeeder::class,
        ]);
    }
}