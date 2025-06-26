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
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            // Only add columns that don't exist yet
            if (!Schema::hasColumn('seed_scrape_uploads', 'original_filename') && Schema::hasColumn('seed_scrape_uploads', 'filename')) {
                $table->renameColumn('filename', 'original_filename');
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'successful_entries') && Schema::hasColumn('seed_scrape_uploads', 'new_entries')) {
                $table->renameColumn('new_entries', 'successful_entries');
            }
            
            if (Schema::hasColumn('seed_scrape_uploads', 'updated_entries')) {
                $table->dropColumn('updated_entries');
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'status')) {
                $table->string('status')->default('pending')->after('supplier_id');
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'notes')) {
                $table->text('notes')->nullable()->after('failed_entries');
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('uploaded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            // Reverse the column renames
            $table->renameColumn('original_filename', 'filename');
            $table->renameColumn('successful_entries', 'new_entries');
            
            // Add back dropped column
            $table->integer('updated_entries')->default(0)->after('successful_entries');
            
            // Drop added columns
            $table->dropColumn([
                'status',
                'notes', 
                'processed_at'
            ]);
        });
    }
};
