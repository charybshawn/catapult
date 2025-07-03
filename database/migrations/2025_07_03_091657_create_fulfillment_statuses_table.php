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
        Schema::create('fulfillment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 50)->default('gray');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_final')->default(false);
            $table->boolean('allows_modifications')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Seed the default fulfillment statuses
        $statuses = [
            ['code' => 'pending', 'name' => 'Pending', 'color' => 'gray', 'sort_order' => 1],
            ['code' => 'processing', 'name' => 'Processing', 'color' => 'blue', 'sort_order' => 2],
            ['code' => 'packing', 'name' => 'Packing', 'color' => 'yellow', 'sort_order' => 3],
            ['code' => 'packed', 'name' => 'Packed', 'color' => 'orange', 'sort_order' => 4],
            ['code' => 'ready_for_delivery', 'name' => 'Ready for Delivery', 'color' => 'purple', 'sort_order' => 5],
            ['code' => 'out_for_delivery', 'name' => 'Out for Delivery', 'color' => 'indigo', 'sort_order' => 6],
            ['code' => 'delivered', 'name' => 'Delivered', 'color' => 'green', 'sort_order' => 7, 'is_final' => true],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'color' => 'red', 'sort_order' => 8, 'is_final' => true],
        ];

        foreach ($statuses as $status) {
            DB::table('fulfillment_statuses')->insert([
                'code' => $status['code'],
                'name' => $status['name'],
                'color' => $status['color'],
                'sort_order' => $status['sort_order'],
                'is_final' => $status['is_final'] ?? false,
                'allows_modifications' => $status['allows_modifications'] ?? true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_statuses');
    }
};