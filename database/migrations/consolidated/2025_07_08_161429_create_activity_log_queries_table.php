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
            $table->unsignedBigInteger('activity_log_id');
            $table->text('sql');
            $table->json('bindings')->nullable();
            $table->decimal('execution_time_ms', 10, 2);
            $table->string('connection_name', 100)->nullable();
            $table->date('query_type')->default('other');
            $table->string('table_name', 100)->nullable();
            $table->integer('rows_affected')->nullable();
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