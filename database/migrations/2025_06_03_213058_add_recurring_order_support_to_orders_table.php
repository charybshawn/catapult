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
        Schema::table('orders', function (Blueprint $table) {
            // Add recurring order support fields
            $table->boolean('is_recurring')->default(false)->after('customer_type');
            $table->foreignId('parent_recurring_order_id')->nullable()->constrained('orders')->nullOnDelete()->after('is_recurring');
            $table->enum('recurring_frequency', ['weekly', 'biweekly', 'monthly'])->nullable()->after('parent_recurring_order_id');
            $table->date('recurring_start_date')->nullable()->after('recurring_frequency');
            $table->date('recurring_end_date')->nullable()->after('recurring_start_date');
            $table->boolean('is_recurring_active')->default(true)->after('recurring_end_date');
            $table->json('recurring_days_of_week')->nullable()->after('is_recurring_active'); // For weekly: [1,3,5] = Mon,Wed,Fri
            $table->integer('recurring_interval')->nullable()->after('recurring_days_of_week'); // For biweekly: every 2 weeks
            $table->timestamp('last_generated_at')->nullable()->after('recurring_interval');
            $table->timestamp('next_generation_date')->nullable()->after('last_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['parent_recurring_order_id']);
            $table->dropColumn([
                'is_recurring',
                'parent_recurring_order_id',
                'recurring_frequency',
                'recurring_start_date',
                'recurring_end_date',
                'is_recurring_active',
                'recurring_days_of_week',
                'recurring_interval',
                'last_generated_at',
                'next_generation_date'
            ]);
        });
    }
};