<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear existing notes data
        DB::table('recipes')->update(['notes' => null]);
        
        // Note: We're not removing the column yet to maintain backward compatibility
        // This will be done in a future migration once we're sure all code references
        // have been updated and the application has been running without issues
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do here since we're just nullifying values
        // The actual column will be removed in a future migration
    }
};
