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
        Schema::create('unified_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 255);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('color', 255);
            $table->string('badge_color', 255)->nullable();
            $table->enum('stage', ['pre_production','production','fulfillment','final']);
            $table->integer('requires_crops')->default(0);
            $table->integer('is_active')->default(1);
            $table->integer('is_final')->default(0);
            $table->integer('allows_modifications')->default(1);
            $table->integer('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_order_statuses');
    }
};