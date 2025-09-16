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
                    $table->id('id');
                    $table->bigInteger('master_cultivar_id');
                    $table->bigInteger('user_id');
                    $table->decimal('total_weight_grams', 10, 2);
                    $table->decimal('tray_count', 8, 2);
                    $table->string('harvest_date'); // TODO: Review type for: date not null
                    $table->string('week_start_date')->nullable(); // TODO: Review type for: date default null
                    $table->text('notes')->nullable();
                    $table->decimal('average_weight_per_tray', 10, 2)->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
