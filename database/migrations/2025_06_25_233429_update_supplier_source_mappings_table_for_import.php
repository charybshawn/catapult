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
        Schema::table('supplier_source_mappings', function (Blueprint $table) {
            // Only add columns that don't exist yet
            if (!Schema::hasColumn('supplier_source_mappings', 'source_url')) {
                $table->string('source_url')->after('supplier_id');
            }
            
            if (!Schema::hasColumn('supplier_source_mappings', 'domain')) {
                $table->string('domain')->after('source_url');
            }
            
            if (!Schema::hasColumn('supplier_source_mappings', 'metadata')) {
                $table->json('metadata')->nullable()->after('mapping_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_source_mappings', function (Blueprint $table) {
            $table->dropColumn([
                'source_url',
                'domain', 
                'metadata'
            ]);
        });
    }
};
