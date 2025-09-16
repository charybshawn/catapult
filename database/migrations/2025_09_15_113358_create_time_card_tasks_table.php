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
        Schema::create('time_card_tasks', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('time_card_id');
                    $table->string('task_name', 255);
                    $table->bigInteger('task_type_id')->nullable();
                    $table->integer('is_custom');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_card_tasks');
    }
};
