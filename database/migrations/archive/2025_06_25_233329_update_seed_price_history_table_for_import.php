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
            // Keep original column name from main branch
            // Only add new columns needed for import functionality
            
            // Add missing columns
            $table->string('currency', 3)->default('USD')->after('price');
            $table->boolean('is_in_stock')->default(true)->after('currency');
            
            // Add virtual column for backward compatibility if needed
            $table->timestamp('scraped_at')->virtualAs('checked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_price_history', function (Blueprint $table) {
            // Drop virtual column
            $table->dropColumn('scraped_at');
            
            // Drop added columns
            $table->dropColumn([
                'currency',
                'is_in_stock'
            ]);
        });
    }
};
