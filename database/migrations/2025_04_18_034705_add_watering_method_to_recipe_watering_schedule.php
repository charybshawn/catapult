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
        Schema::table('recipe_watering_schedule', function (Blueprint $table) {
            $table->string('watering_method')->default('bottom')->after('water_amount_ml')->comment('bottom, top, mist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_watering_schedule', function (Blueprint $table) {
            $table->dropColumn('watering_method');
        });
    }
};
