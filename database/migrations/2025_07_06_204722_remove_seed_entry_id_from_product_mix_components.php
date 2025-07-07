<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Drop foreign key constraint first if it exists
            try {
                $table->dropForeign(['seed_entry_id']);
            } catch (Exception $e) {
                // Foreign key constraint may not exist
            }
            
            $table->dropColumn('seed_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->bigInteger('seed_entry_id')->unsigned()->nullable()->after('master_seed_catalog_id');
            // Note: Foreign key constraint not restored in rollback for simplicity
        });
    }
};