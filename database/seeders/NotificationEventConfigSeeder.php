<?php

namespace Database\Seeders;

use App\Models\NotificationEvent;
use App\Models\NotificationEventConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationEventConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat global config (default) untuk semua event
        $events = NotificationEvent::all();

        foreach ($events as $event) {
            // Set default config untuk setiap event
            $defaultConfig = $this->getDefaultConfig($event->code);
            
            NotificationEventConfig::updateOrCreate(
                [
                    'notification_event_id' => $event->id,
                    'workspace_id' => null,
                    'project_id' => null,
                ],
                $defaultConfig
            );
        }
    }

    /**
     * Get default configuration for each event type
     */
    private function getDefaultConfig(string $eventCode): array
    {
        $defaults = [
            // Task events - notify assignee primarily
            'task_created' => [
                'is_enabled' => true,
                'notify_assignee' => false,
                'notify_creator' => false,
                'notify_project_members' => true, // All members know about new tasks
                'notify_workspace_members' => false,
            ],
            'task_assigned' => [
                'is_enabled' => true,
                'notify_assignee' => true, // ONLY the assigned user
                'notify_creator' => false,  // Creator doesn't need notification
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_unassigned' => [
                'is_enabled' => true,
                'notify_assignee' => true, // ONLY the person being removed
                'notify_creator' => false,  // Creator doesn't need notification
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_status_changed' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => true,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_priority_changed' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_due_date_approaching' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_overdue' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => true, // Creator should know task is overdue
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_completed' => [
                'is_enabled' => true,
                'notify_assignee' => false, // They completed it, they know
                'notify_creator' => true,   // Creator should know
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'task_deleted' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => true,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            
            // Comment events
            'comment_added' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => true,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'comment_mentioned' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The mentioned user
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'comment_replied' => [
                'is_enabled' => true,
                'notify_assignee' => true, // Original commenter
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            
            // Project events
            'project_created' => [
                'is_enabled' => true,
                'notify_assignee' => false,
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => true, // All workspace members
            ],
            'project_member_added' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The new member
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'project_member_removed' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The removed member
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'project_role_changed' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The member whose role changed
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            
            // Workspace events
            'workspace_invitation' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The invited user
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'workspace_member_added' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The new member
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'workspace_member_removed' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The removed member
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            'workspace_role_changed' => [
                'is_enabled' => true,
                'notify_assignee' => true, // The member whose role changed
                'notify_creator' => false,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
            
            // Attachment events
            'file_attached' => [
                'is_enabled' => true,
                'notify_assignee' => true,
                'notify_creator' => true,
                'notify_project_members' => false,
                'notify_workspace_members' => false,
            ],
        ];

        // Return default config if exists, otherwise use generic default
        return $defaults[$eventCode] ?? [
            'is_enabled' => true,
            'notify_assignee' => true,
            'notify_creator' => false,
            'notify_project_members' => false,
            'notify_workspace_members' => false,
        ];
    }
}
