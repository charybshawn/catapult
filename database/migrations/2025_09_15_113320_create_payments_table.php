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
        Schema::create('payments', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('order_id');
                    $table->decimal('amount', 10, 2);
                    $table->bigInteger('payment_method_id')->nullable();
                    $table->bigInteger('status_id');
                    $table->string('transaction_id', 255)->nullable();
                    $table->timestamp('paid_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
