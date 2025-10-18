<?php

namespace Database\Seeders;

use App\Models\NotificationEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            // Task Events
            [
                'code' => 'task_created',
                'name' => 'Task Created',
                'description' => 'Triggered when a new task is created',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_assigned',
                'name' => 'Task Assigned',
                'description' => 'Triggered when a task is assigned to a user',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_unassigned',
                'name' => 'Task Unassigned',
                'description' => 'Triggered when a user is removed from a task',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_status_changed',
                'name' => 'Task Status Changed',
                'description' => 'Triggered when a task status is updated',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_priority_changed',
                'name' => 'Task Priority Changed',
                'description' => 'Triggered when a task priority is updated',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_due_date_approaching',
                'name' => 'Task Due Date Approaching',
                'description' => 'Triggered when a task due date is approaching (1 day before)',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_overdue',
                'name' => 'Task Overdue',
                'description' => 'Triggered when a task is past its due date',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_completed',
                'name' => 'Task Completed',
                'description' => 'Triggered when a task is marked as completed',
                'category' => 'task',
                'is_active' => true,
            ],
            [
                'code' => 'task_deleted',
                'name' => 'Task Deleted',
                'description' => 'Triggered when a task is deleted',
                'category' => 'task',
                'is_active' => true,
            ],
            
            // Comment Events
            [
                'code' => 'comment_added',
                'name' => 'Comment Added',
                'description' => 'Triggered when a comment is added to a task',
                'category' => 'comment',
                'is_active' => true,
            ],
            [
                'code' => 'comment_mentioned',
                'name' => 'User Mentioned in Comment',
                'description' => 'Triggered when a user is mentioned (@username) in a comment',
                'category' => 'comment',
                'is_active' => true,
            ],
            [
                'code' => 'comment_replied',
                'name' => 'Comment Replied',
                'description' => 'Triggered when someone replies to your comment',
                'category' => 'comment',
                'is_active' => true,
            ],
            
            // Project Events
            [
                'code' => 'project_created',
                'name' => 'Project Created',
                'description' => 'Triggered when a new project is created',
                'category' => 'project',
                'is_active' => true,
            ],
            [
                'code' => 'project_member_added',
                'name' => 'Member Added to Project',
                'description' => 'Triggered when a user is added to a project',
                'category' => 'project',
                'is_active' => true,
            ],
            [
                'code' => 'project_member_removed',
                'name' => 'Member Removed from Project',
                'description' => 'Triggered when a user is removed from a project',
                'category' => 'project',
                'is_active' => true,
            ],
            [
                'code' => 'project_role_changed',
                'name' => 'Project Role Changed',
                'description' => 'Triggered when a user\'s role in a project is changed',
                'category' => 'project',
                'is_active' => true,
            ],
            
            // Workspace Events
            [
                'code' => 'workspace_invitation',
                'name' => 'Workspace Invitation',
                'description' => 'Triggered when a user is invited to a workspace',
                'category' => 'workspace',
                'is_active' => true,
            ],
            [
                'code' => 'workspace_member_added',
                'name' => 'Member Added to Workspace',
                'description' => 'Triggered when a user joins a workspace',
                'category' => 'workspace',
                'is_active' => true,
            ],
            [
                'code' => 'workspace_member_removed',
                'name' => 'Member Removed from Workspace',
                'description' => 'Triggered when a user is removed from a workspace',
                'category' => 'workspace',
                'is_active' => true,
            ],
            [
                'code' => 'workspace_role_changed',
                'name' => 'Workspace Role Changed',
                'description' => 'Triggered when a user\'s role in a workspace is changed',
                'category' => 'workspace',
                'is_active' => true,
            ],
            
            // Attachment Events
            [
                'code' => 'file_attached',
                'name' => 'File Attached',
                'description' => 'Triggered when a file is attached to a task',
                'category' => 'attachment',
                'is_active' => true,
            ],
        ];

        foreach ($events as $event) {
            NotificationEvent::updateOrCreate(
                ['code' => $event['code']],
                $event
            );
        }
    }
}
