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
            $table->id();
            $table->unsignedBigInteger('consumable_id');
            $table->string('type'); // consumption, addition, adjustment, waste, expiration
            $table->decimal('quantity', 10, 3); // Amount of transaction (positive or negative)
            $table->decimal('balance_after', 10, 3); // Stock balance after this transaction
            $table->unsignedBigInteger('user_id')->nullable(); // Who performed the transaction
            $table->string('reference_type')->nullable(); // Polymorphic reference type
            $table->unsignedBigInteger('reference_id')->nullable(); // Polymorphic reference ID
            $table->text('notes')->nullable(); // Transaction notes
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('consumable_id')->references('id')->on('consumables')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better performance
            $table->index(['consumable_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_transactions');
    }
};
