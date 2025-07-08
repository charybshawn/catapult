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
        Schema::create('seed_scrape_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('filename', 255);
            $table->integer('total_entries')->default(0);
            $table->integer('new_entries')->default(0);
            $table->integer('updated_entries')->default(0);
            $table->integer('failed_entries_count')->default(0);
            $table->string('status', 255)->default('pending');
            $table->json('failed_entries')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('uploaded_at');
            $table->dateTime('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('successful_entries')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_scrape_uploads');
    }
};