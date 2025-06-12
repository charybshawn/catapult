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
            $table->id();
            $table->string('common_name')->unique()->index();
            $table->string('scientific_name')->nullable();
            $table->string('category')->nullable()->index();
            $table->json('aliases')->nullable(); // Alternative common names
            $table->text('growing_notes')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_seed_catalog');
    }
};
