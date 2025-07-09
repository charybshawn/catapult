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
            $table->string('source_url')->nullable()->after('supplier_id');
            $table->string('domain')->nullable()->after('source_url');
            $table->json('metadata')->nullable()->after('mapping_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_source_mappings', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'domain', 'metadata']);
        });
    }
};