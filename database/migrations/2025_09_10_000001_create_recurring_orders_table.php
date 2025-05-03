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
        Schema::create('recurring_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('Name for this recurring order schedule');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'custom'])
                ->default('weekly')
                ->comment('How often this order repeats');
            $table->json('delivery_days')->comment('Days of the week for delivery (0=Sun, 6=Sat)');
            $table->enum('customer_type', ['retail', 'wholesale'])->default('retail');
            $table->date('start_date')->comment('When this recurring order begins');
            $table->date('end_date')->nullable()->comment('When this recurring order ends (null = indefinite)');
            $table->integer('interval')->default(1)->comment('For custom frequency: interval between orders');
            $table->string('interval_unit')->nullable()->comment('For custom frequency: days, weeks, months');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_orders');
    }
}; 