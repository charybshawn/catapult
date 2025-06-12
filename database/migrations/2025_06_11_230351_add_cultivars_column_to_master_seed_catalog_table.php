<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column exists, if not add it
        if (!Schema::hasColumn('master_seed_catalog', 'cultivars')) {
            Schema::table('master_seed_catalog', function (Blueprint $table) {
                $table->json('cultivars')->nullable()->after('scientific_name');
            });
        }

        // Copy data from scientific_name to cultivars
        DB::table('master_seed_catalog')->whereNotNull('scientific_name')->orderBy('id')->chunk(100, function ($catalogs) {
            foreach ($catalogs as $catalog) {
                DB::table('master_seed_catalog')
                    ->where('id', $catalog->id)
                    ->update(['cultivars' => $catalog->scientific_name]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_seed_catalog', function (Blueprint $table) {
            $table->dropColumn('cultivars');
        });
    }
};
