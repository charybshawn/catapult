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
        Schema::create('tasks', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('title', 255);
                    $table->text('description')->nullable();
                    $table->string('task_type', 50);
                    $table->string('due_date')->nullable(); // TODO: Review type for: date default null
                    $table->timestamp('completed_at')->nullable();
                    $table->bigInteger('assigned_to')->nullable();
                    $table->string('priority', 50);
                    $table->string('status', 50);
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
