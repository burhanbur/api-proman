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
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('notification_event_id')->nullable()->after('user_id')->constrained('notification_events')->onDelete('set null');
            $table->string('related_model_type')->nullable()->after('notification_event_id')->comment('Model type: Task, Project, Comment, etc');
            $table->unsignedBigInteger('related_model_id')->nullable()->after('related_model_type')->comment('ID of related model');
            $table->foreignId('triggered_by')->nullable()->after('related_model_id')->constrained('users')->onDelete('set null')->comment('User who triggered this notification');
            
            $table->index(['user_id', 'is_read']);
            $table->index('notification_event_id');
            $table->index(['related_model_type', 'related_model_id'], 'related_model_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['notification_event_id']);
            $table->dropForeign(['triggered_by']);
            $table->dropIndex(['user_id', 'is_read']);
            $table->dropIndex(['notification_event_id']);
            $table->dropIndex('related_model_idx');
            $table->dropColumn(['notification_event_id', 'related_model_type', 'related_model_id', 'triggered_by']);
        });
    }
};
