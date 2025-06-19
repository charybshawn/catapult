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
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_cultivar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_weight_grams', 10, 2);
            $table->integer('tray_count');
            $table->decimal('average_weight_per_tray', 10, 2)->virtualAs('total_weight_grams / tray_count');
            $table->date('harvest_date');
            $table->date('week_start_date')->virtualAs("DATE_SUB(harvest_date, INTERVAL DAYOFWEEK(harvest_date) - 4 DAY)");
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['master_cultivar_id', 'harvest_date']);
            $table->index('week_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
