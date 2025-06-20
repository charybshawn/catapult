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
            // Rename filename to original_filename to match model
            $table->renameColumn('filename', 'original_filename');
            
            // Add missing fields from model fillable
            $table->string('status')->default('pending')->after('failed_entries_count');
            $table->datetime('processed_at')->nullable()->after('uploaded_at');
            $table->text('notes')->nullable()->after('processed_at');
            $table->integer('successful_entries')->default(0)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            // Rename back to original
            $table->renameColumn('original_filename', 'filename');
            
            // Drop added columns
            $table->dropColumn(['status', 'processed_at', 'notes', 'successful_entries']);
        });
    }
};
