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
            $table->json('failed_entries')->nullable()->after('notes');
            $table->integer('total_entries')->default(0)->after('failed_entries');
            $table->integer('successful_entries')->default(0)->after('total_entries');
            $table->integer('failed_entries_count')->default(0)->after('successful_entries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            $table->dropColumn(['failed_entries', 'total_entries', 'successful_entries', 'failed_entries_count']);
        });
    }
};
