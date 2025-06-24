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
            $table->foreignId('time_card_id')->constrained()->onDelete('cascade');
            $table->string('task_name'); // This will store either task_type name or custom task
            $table->foreignId('task_type_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
            
            $table->index(['time_card_id', 'task_name']);
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
