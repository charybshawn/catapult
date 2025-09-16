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
        Schema::create('master_seed_catalog', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('common_name', 255);
                    $table->bigInteger('cultivar_id')->nullable();
                    $table->string('category', 255)->nullable();
                    $table->json('aliases')->nullable();
                    $table->text('growing_notes')->nullable();
                    $table->text('description')->nullable();
                    $table->integer('is_active');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();
                    $table->json('cultivars')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_seed_catalog');
    }
};
