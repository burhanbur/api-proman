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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->onDelete('cascade');
            $table->string('title_template')->comment('Template for notification title with placeholders {{variable}}');
            $table->text('message_template')->comment('Template for notification message with placeholders {{variable}}');
            $table->enum('type', ['info', 'success', 'warning', 'error'])->default('info')->comment('Default notification type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('notification_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
