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
        Schema::create('weight_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->text('description')->nullable();
            $table->decimal('conversion_factor', 15, 8)->default(1); // Conversion to base unit (grams)
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Seed the default weight units
        $units = [
            ['code' => 'mg', 'name' => 'Milligrams', 'symbol' => 'mg', 'conversion_factor' => 0.001, 'sort_order' => 1],
            ['code' => 'g', 'name' => 'Grams', 'symbol' => 'g', 'conversion_factor' => 1, 'sort_order' => 2],
            ['code' => 'kg', 'name' => 'Kilograms', 'symbol' => 'kg', 'conversion_factor' => 1000, 'sort_order' => 3],
            ['code' => 'oz', 'name' => 'Ounces', 'symbol' => 'oz', 'conversion_factor' => 28.3495, 'sort_order' => 4],
            ['code' => 'lbs', 'name' => 'Pounds', 'symbol' => 'lbs', 'conversion_factor' => 453.592, 'sort_order' => 5],
        ];

        foreach ($units as $unit) {
            DB::table('weight_units')->insert([
                'code' => $unit['code'],
                'name' => $unit['name'],
                'symbol' => $unit['symbol'],
                'conversion_factor' => $unit['conversion_factor'],
                'sort_order' => $unit['sort_order'],
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
        Schema::dropIfExists('weight_units');
    }
};