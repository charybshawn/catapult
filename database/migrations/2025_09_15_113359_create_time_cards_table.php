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
                    $table->id('id');
                    $table->bigInteger('user_id');
                    $table->timestamp('clock_in');
                    $table->timestamp('clock_out')->nullable();
                    $table->integer('duration_minutes')->nullable();
                    $table->string('work_date'); // TODO: Review type for: date not null
                    $table->bigInteger('time_card_status_id');
                    $table->integer('max_shift_exceeded');
                    $table->timestamp('max_shift_exceeded_at')->nullable();
                    $table->integer('requires_review');
                    $table->json('flags')->nullable();
                    $table->text('review_notes')->nullable();
                    $table->text('notes')->nullable();
                    $table->string('ip_address', 255)->nullable();
                    $table->string('user_agent', 255)->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_cards');
    }
};
