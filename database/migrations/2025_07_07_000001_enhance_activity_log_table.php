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
        Schema::table('activity_log', function (Blueprint $table) {
            // Network and request information
            $table->string('ip_address', 45)->nullable()->after('batch_uuid');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('request_method', 10)->nullable()->after('user_agent');
            $table->text('request_url')->nullable()->after('request_method');
            $table->integer('response_status')->nullable()->after('request_url');
            
            // Performance metrics
            $table->decimal('execution_time_ms', 10, 2)->nullable()->after('response_status');
            $table->decimal('memory_usage_mb', 10, 2)->nullable()->after('execution_time_ms');
            $table->integer('query_count')->nullable()->after('memory_usage_mb');
            
            // Enhanced categorization
            $table->json('context')->nullable()->after('query_count');
            $table->json('tags')->nullable()->after('context');
            $table->enum('severity_level', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])
                ->default('info')
                ->after('tags');
            
            // Add indexes for performance
            $table->index('created_at');
            $table->index('severity_level');
            $table->index(['log_name', 'created_at']);
            $table->index(['causer_type', 'causer_id', 'created_at']);
            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index('ip_address');
            
            // Add full-text index for description if using MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['created_at']);
            $table->dropIndex(['severity_level']);
            $table->dropIndex(['log_name', 'created_at']);
            $table->dropIndex(['causer_type', 'causer_id', 'created_at']);
            $table->dropIndex(['subject_type', 'subject_id', 'created_at']);
            $table->dropIndex(['event', 'created_at']);
            $table->dropIndex(['ip_address']);
            
            if (config('database.default') === 'mysql') {
                $table->dropFullText(['description']);
            }
            
            // Drop columns
            $table->dropColumn([
                'ip_address',
                'user_agent',
                'request_method',
                'request_url',
                'response_status',
                'execution_time_ms',
                'memory_usage_mb',
                'query_count',
                'context',
                'tags',
                'severity_level'
            ]);
        });
    }
};