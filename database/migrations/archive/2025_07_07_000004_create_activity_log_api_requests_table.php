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
        Schema::create('activity_log_api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->nullable()->constrained('activity_log')->cascadeOnDelete();
            $table->string('endpoint', 500);
            $table->string('method', 10);
            $table->string('api_version', 20)->nullable();
            $table->string('client_id', 100)->nullable();
            $table->string('api_key_id', 100)->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('query_parameters')->nullable();
            $table->integer('response_status');
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->decimal('response_time_ms', 10, 2);
            $table->integer('response_size_bytes')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('is_authenticated')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->json('rate_limit_info')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('activity_log_id');
            $table->index('endpoint');
            $table->index('method');
            $table->index('api_version');
            $table->index('client_id');
            $table->index('response_status');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['endpoint', 'method', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_api_requests');
    }
};