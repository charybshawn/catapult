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
        Schema::create('product_inventory_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 20)->default('gray');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // Insert default statuses
        DB::table('product_inventory_statuses')->insert([
            ['code' => 'active', 'name' => 'Active', 'description' => 'Inventory is available for use', 'color' => 'green', 'sort_order' => 1],
            ['code' => 'depleted', 'name' => 'Depleted', 'description' => 'Inventory has been fully consumed', 'color' => 'gray', 'sort_order' => 2],
            ['code' => 'expired', 'name' => 'Expired', 'description' => 'Inventory has passed its expiration date', 'color' => 'red', 'sort_order' => 3],
            ['code' => 'damaged', 'name' => 'Damaged', 'description' => 'Inventory is damaged and unusable', 'color' => 'orange', 'sort_order' => 4],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventory_statuses');
    }
};