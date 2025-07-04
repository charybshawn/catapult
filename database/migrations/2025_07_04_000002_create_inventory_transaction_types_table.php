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
        Schema::create('inventory_transaction_types', function (Blueprint $table) {
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

        // Insert default types
        DB::table('inventory_transaction_types')->insert([
            ['code' => 'purchase', 'name' => 'Purchase', 'description' => 'Inventory received from purchase', 'color' => 'green', 'sort_order' => 1],
            ['code' => 'sale', 'name' => 'Sale', 'description' => 'Inventory sold to customer', 'color' => 'blue', 'sort_order' => 2],
            ['code' => 'adjustment', 'name' => 'Adjustment', 'description' => 'Manual inventory adjustment', 'color' => 'yellow', 'sort_order' => 3],
            ['code' => 'return', 'name' => 'Return', 'description' => 'Inventory returned', 'color' => 'purple', 'sort_order' => 4],
            ['code' => 'damage', 'name' => 'Damage', 'description' => 'Inventory damaged or lost', 'color' => 'red', 'sort_order' => 5],
            ['code' => 'transfer', 'name' => 'Transfer', 'description' => 'Inventory transferred between locations', 'color' => 'indigo', 'sort_order' => 6],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction_types');
    }
};