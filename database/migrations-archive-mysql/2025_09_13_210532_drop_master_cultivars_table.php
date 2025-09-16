<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, remove foreign key constraint from master_seed_catalog table
        Schema::table('master_seed_catalog', function (Blueprint $table) {
            $table->dropForeign(['cultivar_id']);
            $table->dropColumn('cultivar_id');
        });
        
        // Then drop the master_cultivars table
        Schema::dropIfExists('master_cultivars');
    }

    public function down(): void
    {
        // Recreate the master_cultivars table
        Schema::create('master_cultivars', function (Blueprint $table) {
            $table->id();
            $table->string('cultivar_name');
            $table->unsignedBigInteger('master_seed_catalog_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('cascade');
            $table->unique(['cultivar_name', 'master_seed_catalog_id']);
        });
        
        // Re-add the cultivar_id column to master_seed_catalog
        Schema::table('master_seed_catalog', function (Blueprint $table) {
            $table->unsignedBigInteger('cultivar_id')->nullable()->after('id');
            $table->foreign('cultivar_id')->references('id')->on('master_cultivars')->onDelete('set null');
        });
    }
};
