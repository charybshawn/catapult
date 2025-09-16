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
                    $table->id('id');
                    $table->bigInteger('supplier_id');
                    $table->string('filename', 255);
                    $table->integer('total_entries');
                    $table->integer('new_entries');
                    $table->integer('updated_entries');
                    $table->integer('failed_entries_count');
                    $table->string('status', 255);
                    $table->json('failed_entries')->nullable();
                    $table->bigInteger('uploaded_by');
                    $table->timestamp('uploaded_at');
                    $table->timestamp('processed_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->integer('successful_entries');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_scrape_uploads');
    }
};
