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
        // First, drop the foreign key constraint if it exists
        Schema::table('crops', function (Blueprint $table) {
            // Drop the foreign key if it exists
            if (Schema::hasColumn('crops', 'order_id')) {
                $table->dropForeign(['order_id']);
                $table->dropColumn('order_id');
            }
        });
        
        // Now add the column back with the proper foreign key
        Schema::table('crops', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('recipe_id')->constrained('orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
        
        Schema::table('crops', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('recipe_id');
        });
    }
};
