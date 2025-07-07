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
        Schema::create('activity_log_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->constrained('activity_log')->cascadeOnDelete();
            $table->text('sql');
            $table->json('bindings')->nullable();
            $table->decimal('execution_time_ms', 10, 2);
            $table->string('connection_name', 100)->nullable();
            $table->enum('query_type', ['select', 'insert', 'update', 'delete', 'create', 'drop', 'alter', 'other'])
                ->default('other');
            $table->string('table_name', 100)->nullable();
            $table->integer('rows_affected')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('activity_log_id');
            $table->index('query_type');
            $table->index('table_name');
            $table->index('execution_time_ms');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_queries');
    }
};