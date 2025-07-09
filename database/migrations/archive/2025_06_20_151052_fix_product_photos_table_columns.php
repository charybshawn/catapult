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
        Schema::table('product_photos', function (Blueprint $table) {
            // Rename columns to match model expectations
            $table->renameColumn('filename', 'photo');
            $table->renameColumn('display_order', 'order');
            
            // Add missing is_default column
            $table->boolean('is_default')->default(false)->after('photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_photos', function (Blueprint $table) {
            // Revert column renames
            $table->renameColumn('photo', 'filename');
            $table->renameColumn('order', 'display_order');
            
            // Remove is_default column
            $table->dropColumn('is_default');
        });
    }
};
