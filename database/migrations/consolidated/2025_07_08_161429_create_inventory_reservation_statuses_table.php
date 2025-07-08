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
        Schema::create('inventory_reservation_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 50)->default('gray');
            $table->integer('is_active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->integer('is_final')->default(0);
            $table->integer('allows_modifications')->default(1);
            $table->integer('auto_release_hours')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservation_statuses');
    }
};