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
        Schema::create('crop_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type', 255);
            $table->json('conditions');
            $table->integer('is_active')->default(1);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_alerts');
    }
};