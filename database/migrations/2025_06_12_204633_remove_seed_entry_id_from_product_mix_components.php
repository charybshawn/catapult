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
            // Drop the foreign key constraint first
            $table->dropForeign(['seed_entry_id']);
            
            // Then drop the column
            $table->dropColumn('seed_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Re-add the column as nullable
            $table->foreignId('seed_entry_id')
                ->nullable()
                ->after('product_mix_id')
                ->constrained('seed_entries')
                ->onDelete('cascade');
        });
    }
};