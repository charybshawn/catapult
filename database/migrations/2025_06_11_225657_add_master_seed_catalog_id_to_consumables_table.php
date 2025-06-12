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
        Schema::table('consumables', function (Blueprint $table) {
            // Check if column exists, if not add it
            if (!Schema::hasColumn('consumables', 'master_seed_catalog_id')) {
                $table->unsignedBigInteger('master_seed_catalog_id')->nullable()->after('seed_entry_id');
            }
            
            // Add foreign key constraint
            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['master_seed_catalog_id']);
            $table->dropColumn('master_seed_catalog_id');
        });
    }
};
