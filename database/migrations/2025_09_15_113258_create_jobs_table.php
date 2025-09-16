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
        Schema::create('jobs', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('queue', 255);
                    $table->text('payload');
                    $table->integer('attempts');
                    $table->integer('reserved_at')->nullable();
                    $table->integer('available_at');
                    $table->timestamp('created_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
