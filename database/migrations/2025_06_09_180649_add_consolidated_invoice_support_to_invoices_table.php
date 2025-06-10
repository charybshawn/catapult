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
        Schema::table('invoices', function (Blueprint $table) {
            // Add user_id for consolidated invoices that may not have a single order
            $table->unsignedBigInteger('user_id')->nullable()->after('order_id');
            
            // Add total_amount for consolidated invoices
            $table->decimal('total_amount', 10, 2)->nullable()->after('amount');
            
            // Add issue_date
            $table->date('issue_date')->nullable()->after('total_amount');
            
            // Add billing period fields
            $table->date('billing_period_start')->nullable()->after('due_date');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
            
            // Add consolidated invoice fields
            $table->boolean('is_consolidated')->default(false)->after('billing_period_end');
            $table->integer('consolidated_order_count')->nullable()->after('is_consolidated');
            
            // Add foreign key for user
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Update status enum to include 'pending'
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'total_amount',
                'issue_date',
                'billing_period_start',
                'billing_period_end',
                'is_consolidated',
                'consolidated_order_count'
            ]);
        });
    }
};