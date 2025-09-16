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
        Schema::create('fulfillment_statuses', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('code', 50);
                    $table->string('name', 100);
                    $table->text('description')->nullable();
                    $table->string('color', 50);
                    $table->integer('is_active');
                    $table->integer('sort_order');
                    $table->integer('is_final');
                    $table->integer('allows_modifications');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_statuses');
    }
};
