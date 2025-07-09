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
            // Keep original column names from main branch
            // Only add new columns needed for import functionality
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'status')) {
                $table->string('status')->default('pending')->after('supplier_id');
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'notes')) {
                $table->text('notes')->nullable()->after('failed_entries');
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('uploaded_at');
            }
            
            // Add virtual columns for backward compatibility if needed
            if (!Schema::hasColumn('seed_scrape_uploads', 'original_filename')) {
                $table->string('original_filename')->virtualAs('filename')->nullable();
            }
            
            if (!Schema::hasColumn('seed_scrape_uploads', 'successful_entries')) {
                $table->integer('successful_entries')->virtualAs('new_entries')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            // Drop virtual columns
            if (Schema::hasColumn('seed_scrape_uploads', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
            
            if (Schema::hasColumn('seed_scrape_uploads', 'successful_entries')) {
                $table->dropColumn('successful_entries');
            }
            
            // Drop added columns
            $table->dropColumn([
                'status',
                'notes', 
                'processed_at'
            ]);
        });
    }
};
