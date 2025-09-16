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
        Schema::create('consumable_transactions', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('consumable_id');
                    $table->string('type', 255);
                    $table->decimal('quantity', 10, 3);
                    $table->decimal('balance_after', 10, 3);
                    $table->bigInteger('user_id')->nullable();
                    $table->string('reference_type', 255)->nullable();
                    $table->bigInteger('reference_id')->nullable();
                    $table->text('notes')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_transactions');
    }
};
