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
        Schema::create('seed_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seed_variation_id');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('is_in_stock')->default(1);
            $table->timestamp('checked_at');
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_price_history');
    }
};