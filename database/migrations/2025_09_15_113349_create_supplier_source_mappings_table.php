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
        Schema::create('supplier_source_mappings', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('supplier_id');
                    $table->string('source_url', 255)->nullable();
                    $table->string('domain', 255)->nullable();
                    $table->string('source_name', 255);
                    $table->string('source_identifier', 255);
                    $table->json('mapping_data')->nullable();
                    $table->json('metadata')->nullable();
                    $table->integer('is_active');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_mappings');
    }
};
