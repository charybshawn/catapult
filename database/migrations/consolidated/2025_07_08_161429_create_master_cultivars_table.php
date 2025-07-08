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
        Schema::create('master_cultivars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_seed_catalog_id');
            $table->string('cultivar_name', 255);
            $table->text('description')->nullable();
            $table->integer('is_active')->default(1);
            $table->json('aliases')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_cultivars');
    }
};