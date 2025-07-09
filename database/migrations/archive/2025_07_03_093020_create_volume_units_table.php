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
        Schema::create('volume_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->text('description')->nullable();
            $table->decimal('conversion_factor', 15, 8)->default(1); // Conversion to base unit (ml)
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Seed the default volume units
        $units = [
            ['code' => 'ml', 'name' => 'Milliliters', 'symbol' => 'ml', 'conversion_factor' => 1, 'sort_order' => 1],
            ['code' => 'l', 'name' => 'Liters', 'symbol' => 'l', 'conversion_factor' => 1000, 'sort_order' => 2],
            ['code' => 'oz', 'name' => 'Ounces', 'symbol' => 'oz', 'conversion_factor' => 29.5735, 'sort_order' => 3],
            ['code' => 'pt', 'name' => 'Pints', 'symbol' => 'pt', 'conversion_factor' => 473.176, 'sort_order' => 4],
            ['code' => 'qt', 'name' => 'Quarts', 'symbol' => 'qt', 'conversion_factor' => 946.353, 'sort_order' => 5],
            ['code' => 'gal', 'name' => 'Gallons', 'symbol' => 'gal', 'conversion_factor' => 3785.41, 'sort_order' => 6],
        ];

        foreach ($units as $unit) {
            DB::table('volume_units')->insert([
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
        Schema::dropIfExists('volume_units');
    }
};