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
        Schema::create('order_statuses', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('code', 255);
                    $table->string('name', 255);
                    $table->text('description')->nullable();
                    $table->string('color', 255);
                    $table->string('badge_color', 255)->nullable();
                    $table->string('stage', 50);
                    $table->integer('requires_crops');
                    $table->integer('is_active');
                    $table->integer('is_final');
                    $table->integer('allows_modifications');
                    $table->integer('sort_order');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
