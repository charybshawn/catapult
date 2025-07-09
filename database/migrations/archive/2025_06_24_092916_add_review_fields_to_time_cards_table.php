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
        Schema::table('time_cards', function (Blueprint $table) {
            $table->boolean('max_shift_exceeded')->default(false)->after('status');
            $table->datetime('max_shift_exceeded_at')->nullable()->after('max_shift_exceeded');
            $table->boolean('requires_review')->default(false)->after('max_shift_exceeded_at');
            $table->json('flags')->nullable()->after('requires_review');
            $table->text('review_notes')->nullable()->after('flags');
            
            $table->index(['requires_review']);
            $table->index(['max_shift_exceeded']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_cards', function (Blueprint $table) {
            $table->dropIndex(['requires_review']);
            $table->dropIndex(['max_shift_exceeded']);
            $table->dropColumn([
                'max_shift_exceeded',
                'max_shift_exceeded_at', 
                'requires_review',
                'flags',
                'review_notes'
            ]);
        });
    }
};
