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
        Schema::create('time_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->date('work_date');
            $table->unsignedBigInteger('time_card_status_id');
            $table->integer('max_shift_exceeded')->default(0);
            $table->dateTime('max_shift_exceeded_at')->nullable();
            $table->integer('requires_review')->default(0);
            $table->json('flags')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->string('user_agent', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_cards');
    }
};