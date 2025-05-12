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
        Schema::table('crops', function (Blueprint $table) {
            // Change stage_age_minutes to signed integer (allow negative values)
            $table->integer('stage_age_minutes')->nullable()->change();
            
            // Change time_to_next_stage_minutes to signed integer
            $table->integer('time_to_next_stage_minutes')->nullable()->change();
            
            // Change total_age_minutes to signed integer
            $table->integer('total_age_minutes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Change back to unsigned
            $table->unsignedInteger('stage_age_minutes')->nullable()->change();
            $table->unsignedInteger('time_to_next_stage_minutes')->nullable()->change();
            $table->unsignedInteger('total_age_minutes')->nullable()->change();
        });
    }
}; 