<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate cultivars JSON column with existing primary cultivar names
        $catalogs = DB::table('master_seed_catalog')
            ->whereNotNull('cultivar_id')
            ->get();

        foreach ($catalogs as $catalog) {
            $cultivarName = DB::table('master_cultivars')
                ->where('id', $catalog->cultivar_id)
                ->value('cultivar_name');

            if ($cultivarName) {
                DB::table('master_seed_catalog')
                    ->where('id', $catalog->id)
                    ->update(['cultivars' => json_encode([$cultivarName])]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the cultivars column
        DB::table('master_seed_catalog')->update(['cultivars' => null]);
    }
};
