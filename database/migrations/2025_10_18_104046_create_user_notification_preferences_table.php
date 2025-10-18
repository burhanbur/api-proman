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
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('notification_event_id')->constrained('notification_events')->onDelete('cascade');
            $table->boolean('is_enabled')->default(true)->comment('Whether user wants to receive this notification');
            $table->boolean('channel_email')->default(false)->comment('Receive via email');
            $table->boolean('channel_push')->default(false)->comment('Receive via push notification');
            $table->boolean('channel_in_app')->default(true)->comment('Receive in-app notification');
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_event_id'], 'user_event_unique');
            $table->index('user_id');
            $table->index('notification_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
