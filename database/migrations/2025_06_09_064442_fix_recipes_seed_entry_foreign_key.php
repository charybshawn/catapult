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
        Schema::table('recipes', function (Blueprint $table) {
            // Drop the old foreign key constraint pointing to seed_cultivars
            $table->dropForeign(['seed_cultivar_id']);
            
            // Add the new foreign key constraint pointing to seed_entries
            $table->foreign('seed_cultivar_id')->references('id')->on('seed_entries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['seed_cultivar_id']);
            
            // Add back the old foreign key constraint pointing to seed_cultivars
            $table->foreign('seed_cultivar_id')->references('id')->on('seed_cultivars')->onDelete('cascade');
        });
    }
};
