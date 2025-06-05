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
        Schema::dropIfExists('seed_varieties');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('seed_varieties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('crop_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};