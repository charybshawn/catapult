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
        Schema::create('crop_stage_history', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('crop_id');
                    $table->bigInteger('crop_batch_id');
                    $table->bigInteger('stage_id');
                    $table->timestamp('entered_at');
                    $table->timestamp('exited_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->bigInteger('created_by')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_stage_history');
    }
};
