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
        Schema::table('seed_price_history', function (Blueprint $table) {
            // Rename checked_at to scraped_at
            $table->renameColumn('checked_at', 'scraped_at');
            
            // Add missing columns
            $table->string('currency', 3)->default('USD')->after('price');
            $table->boolean('is_in_stock')->default(true)->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_price_history', function (Blueprint $table) {
            // Reverse the column rename
            $table->renameColumn('scraped_at', 'checked_at');
            
            // Drop added columns
            $table->dropColumn([
                'currency',
                'is_in_stock'
            ]);
        });
    }
};
