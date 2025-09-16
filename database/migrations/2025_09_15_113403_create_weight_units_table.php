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
        Schema::create('weight_units', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('code', 10);
                    $table->string('name', 50);
                    $table->string('symbol', 10);
                    $table->text('description')->nullable();
                    $table->decimal('conversion_factor', 15, 8);
                    $table->integer('is_active');
                    $table->integer('sort_order');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weight_units');
    }
};
