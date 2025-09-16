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
        // Add crop_batch_id to crops table if it doesn't exist
        if (!Schema::hasColumn('crops', 'crop_batch_id')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->bigInteger('crop_batch_id')->nullable()->after('id');
                $table->foreign('crop_batch_id')->references('id')->on('crop_batches')->onDelete('cascade');
                $table->index('crop_batch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropForeign(['crop_batch_id']);
            $table->dropColumn('crop_batch_id');
        });
    }
};