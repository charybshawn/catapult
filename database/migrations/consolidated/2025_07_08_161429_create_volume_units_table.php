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
        Schema::create('volume_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->text('description')->nullable();
            $table->decimal('conversion_factor', 15, 8)->default(1.00000000);
            $table->integer('is_active')->default(1);
            $table->integer('sort_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volume_units');
    }
};