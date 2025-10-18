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
        Schema::create('notification_event_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->onDelete('cascade');
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->onDelete('cascade')->comment('NULL = global config');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade')->comment('NULL = workspace level config');
            $table->boolean('is_enabled')->default(true)->comment('Enable/disable notification for this event');
            $table->boolean('notify_assignee')->default(true)->comment('Notify the person assigned to the task');
            $table->boolean('notify_creator')->default(false)->comment('Notify the person who created the task/item');
            $table->boolean('notify_project_members')->default(false)->comment('Notify all project members');
            $table->boolean('notify_workspace_members')->default(false)->comment('Notify all workspace members');
            $table->json('conditions')->nullable()->comment('Advanced conditions for triggering notification');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('notification_event_id');
            $table->index('workspace_id');
            $table->index('project_id');
            $table->index(['notification_event_id', 'workspace_id', 'project_id'], 'event_workspace_project_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_event_configs');
    }
};
