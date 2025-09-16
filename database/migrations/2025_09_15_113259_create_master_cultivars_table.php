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
                    $table->id('id');
                    $table->bigInteger('master_seed_catalog_id');
                    $table->string('cultivar_name', 255);
                    $table->text('description')->nullable();
                    $table->integer('is_active');
                    $table->json('aliases')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_cultivars');
    }
};
