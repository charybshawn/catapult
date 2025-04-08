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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type'); // The type of resource (inventory, crop, order, etc.)
            $table->string('event_type'); // Type of event (low_stock, due_date, etc.)
            $table->json('recipients'); // JSON array of email addresses
            $table->boolean('email_enabled')->default(true); // Whether email notifications are enabled
            $table->string('email_subject_template'); // Template for email subject
            $table->text('email_body_template'); // Template for email body
            $table->boolean('is_active')->default(true); // Whether this notification is active
            $table->timestamps();

            // Unique index to prevent duplicate settings
            $table->unique(['resource_type', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
