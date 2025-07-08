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
            $table->id();
            $table->unsignedBigInteger('time_card_id');
            $table->string('task_name', 255);
            $table->unsignedBigInteger('task_type_id')->nullable();
            $table->integer('is_custom')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_card_tasks');
    }
};