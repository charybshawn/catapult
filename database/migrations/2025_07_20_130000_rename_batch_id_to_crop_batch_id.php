<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename batch_id to crop_batch_id for clarity.
     */
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->renameColumn('batch_id', 'crop_batch_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->renameColumn('crop_batch_id', 'batch_id');
        });
    }
};