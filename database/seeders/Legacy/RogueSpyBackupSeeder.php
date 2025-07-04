<?php

namespace Database\Seeders\Legacy;

use Illuminate\Database\Seeder;

class RogueSpyBackupSeeder extends Seeder
{
    /**
     * Seed the application's database with data from RogueSpyBackup.
     */
    public function run(): void
    {
        $this->call(MasterSeedCatalogTableSeeder::class);

    }
}