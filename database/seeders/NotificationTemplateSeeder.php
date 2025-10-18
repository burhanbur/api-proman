<?php

namespace Database\Seeders;

use App\Models\NotificationEvent;
use App\Models\NotificationTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Task Events
            [
                'event_code' => 'task_created',
                'title_template' => 'New Task: {{task_title}}',
                'message_template' => '{{creator_name}} created a new task "{{task_title}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'task_assigned',
                'title_template' => 'Task Assigned to You',
                'message_template' => '{{assigner_name}} assigned task "{{task_title}}" to you',
                'type' => 'info',
            ],
            [
                'event_code' => 'task_unassigned',
                'title_template' => 'Removed from Task',
                'message_template' => '{{remover_name}} removed you from task "{{task_title}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'task_status_changed',
                'title_template' => 'Task Status Updated',
                'message_template' => '{{updater_name}} changed status of "{{task_title}}" from {{old_status}} to {{new_status}}',
                'type' => 'info',
            ],
            [
                'event_code' => 'task_priority_changed',
                'title_template' => 'Task Priority Updated',
                'message_template' => '{{updater_name}} changed priority of "{{task_title}}" to {{new_priority}}',
                'type' => 'warning',
            ],
            [
                'event_code' => 'task_due_date_approaching',
                'title_template' => 'Task Due Soon',
                'message_template' => 'Task "{{task_title}}" is due on {{due_date}}',
                'type' => 'warning',
            ],
            [
                'event_code' => 'task_overdue',
                'title_template' => 'Task Overdue',
                'message_template' => 'Task "{{task_title}}" is overdue! Due date was {{due_date}}',
                'type' => 'error',
            ],
            [
                'event_code' => 'task_completed',
                'title_template' => 'Task Completed',
                'message_template' => '{{completer_name}} marked task "{{task_title}}" as completed',
                'type' => 'success',
            ],
            [
                'event_code' => 'task_deleted',
                'title_template' => 'Task Deleted',
                'message_template' => '{{deleter_name}} deleted task "{{task_title}}"',
                'type' => 'info',
            ],
            
            // Comment Events
            [
                'event_code' => 'comment_added',
                'title_template' => 'New Comment on Task',
                'message_template' => '{{commenter_name}} commented on "{{task_title}}": {{comment_preview}}',
                'type' => 'info',
            ],
            [
                'event_code' => 'comment_mentioned',
                'title_template' => 'You Were Mentioned',
                'message_template' => '{{commenter_name}} mentioned you in a comment on "{{task_title}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'comment_replied',
                'title_template' => 'Reply to Your Comment',
                'message_template' => '{{replier_name}} replied to your comment on "{{task_title}}"',
                'type' => 'info',
            ],
            
            // Project Events
            [
                'event_code' => 'project_created',
                'title_template' => 'New Project Created',
                'message_template' => '{{creator_name}} created project "{{project_name}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'project_member_added',
                'title_template' => 'Added to Project',
                'message_template' => '{{adder_name}} added you to project "{{project_name}}" as {{role_name}}',
                'type' => 'info',
            ],
            [
                'event_code' => 'project_member_removed',
                'title_template' => 'Removed from Project',
                'message_template' => '{{remover_name}} removed you from project "{{project_name}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'project_role_changed',
                'title_template' => 'Project Role Changed',
                'message_template' => '{{changer_name}} changed your role in "{{project_name}}" to {{new_role}}',
                'type' => 'info',
            ],
            
            // Workspace Events
            [
                'event_code' => 'workspace_invitation',
                'title_template' => 'Workspace Invitation',
                'message_template' => '{{inviter_name}} invited you to join workspace "{{workspace_name}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'workspace_member_added',
                'title_template' => 'Added to Workspace',
                'message_template' => 'You have been added to workspace "{{workspace_name}}" as {{role_name}}',
                'type' => 'info',
            ],
            [
                'event_code' => 'workspace_member_removed',
                'title_template' => 'Removed from Workspace',
                'message_template' => 'You have been removed from workspace "{{workspace_name}}"',
                'type' => 'info',
            ],
            [
                'event_code' => 'workspace_role_changed',
                'title_template' => 'Workspace Role Changed',
                'message_template' => 'Your role in workspace "{{workspace_name}}" has been changed to {{new_role}}',
                'type' => 'info',
            ],
            
            // Attachment Events
            [
                'event_code' => 'file_attached',
                'title_template' => 'File Attached to Task',
                'message_template' => '{{uploader_name}} attached file "{{file_name}}" to task "{{task_title}}"',
                'type' => 'info',
            ],
        ];

        foreach ($templates as $templateData) {
            $event = NotificationEvent::where('code', $templateData['event_code'])->first();
            
            if ($event) {
                NotificationTemplate::updateOrCreate(
                    [
                        'notification_event_id' => $event->id,
                    ],
                    [
                        'title_template' => $templateData['title_template'],
                        'message_template' => $templateData['message_template'],
                        'type' => $templateData['type'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
