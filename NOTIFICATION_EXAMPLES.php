<?php

/**
 * CONTOH IMPLEMENTASI NOTIFICATION SYSTEM
 * 
 * File ini berisi contoh-contoh implementasi untuk trigger notification
 * di berbagai controller dalam aplikasi ProMan.
 */

// ============================================
// 1. TASK CONTROLLER - Contoh Implementasi
// ============================================

namespace App\Http\Controllers\Api;

use App\Services\NotificationService;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create new task
     */
    public function store(Request $request)
    {
        // Validation & create task logic...
        $task = Task::create($request->validated());

        // 🔔 Trigger notification: task_created
        $this->notificationService->trigger('task_created', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'creator_id' => auth()->id(),
            'creator_name' => auth()->user()->name,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Task',
            'model_id' => $task->id,
            'detail_url' => "/tasks/{$task->id}",
        ]);

        return response()->json($task);
    }

    /**
     * Assign task to user(s)
     */
    public function assignTask(Request $request, Task $task)
    {
        $assigneeIds = $request->assignee_ids; // bisa array atau single ID
        
        // Save assignments
        foreach ((array) $assigneeIds as $assigneeId) {
            $task->assignees()->attach($assigneeId, [
                'assigned_by' => auth()->id(),
            ]);

            // 🔔 Trigger notification untuk setiap assignee
            $this->notificationService->trigger('task_assigned', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assignee_id' => $assigneeId,
                'assigner_id' => auth()->id(),
                'assigner_name' => auth()->user()->name,
                'creator_id' => $task->created_by,
                'project_id' => $task->project_id,
                'workspace_id' => $task->project->workspace_id,
                'triggered_by' => auth()->id(),
                'model_type' => 'Task',
                'model_id' => $task->id,
                'detail_url' => "/tasks/{$task->id}",
            ]);
        }

        return response()->json(['message' => 'Task assigned successfully']);
    }

    /**
     * Unassign user from task
     */
    public function unassignTask(Request $request, Task $task, $userId)
    {
        $task->assignees()->detach($userId);

        // 🔔 Trigger notification
        $this->notificationService->trigger('task_unassigned', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'assignee_id' => $userId,
            'remover_id' => auth()->id(),
            'remover_name' => auth()->user()->name,
            'creator_id' => $task->created_by,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Task',
            'model_id' => $task->id,
            'detail_url' => "/tasks/{$task->id}",
        ]);

        return response()->json(['message' => 'User unassigned successfully']);
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, Task $task)
    {
        $oldStatus = $task->status;
        $newStatus = ProjectStatus::find($request->status_id);
        
        $task->update(['status_id' => $request->status_id]);

        // 🔔 Trigger notification
        $this->notificationService->trigger('task_status_changed', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'old_status' => $oldStatus->name,
            'new_status' => $newStatus->name,
            'updater_id' => auth()->id(),
            'updater_name' => auth()->user()->name,
            'assignee_id' => $task->assignees->pluck('id')->toArray(),
            'creator_id' => $task->created_by,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Task',
            'model_id' => $task->id,
            'detail_url' => "/tasks/{$task->id}",
        ]);

        // 🔔 Special: Jika status completed
        if ($newStatus->is_completed) {
            $this->notificationService->trigger('task_completed', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'completer_id' => auth()->id(),
                'completer_name' => auth()->user()->name,
                'assignee_id' => $task->assignees->pluck('id')->toArray(),
                'creator_id' => $task->created_by,
                'project_id' => $task->project_id,
                'workspace_id' => $task->project->workspace_id,
                'triggered_by' => auth()->id(),
                'model_type' => 'Task',
                'model_id' => $task->id,
                'detail_url' => "/tasks/{$task->id}",
            ]);
        }

        return response()->json($task);
    }

    /**
     * Update task priority
     */
    public function updatePriority(Request $request, Task $task)
    {
        $oldPriority = $task->priority;
        $newPriority = Priority::find($request->priority_id);
        
        $task->update(['priority_id' => $request->priority_id]);

        // 🔔 Trigger notification
        $this->notificationService->trigger('task_priority_changed', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'old_priority' => $oldPriority->name,
            'new_priority' => $newPriority->name,
            'updater_id' => auth()->id(),
            'updater_name' => auth()->user()->name,
            'assignee_id' => $task->assignees->pluck('id')->toArray(),
            'creator_id' => $task->created_by,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Task',
            'model_id' => $task->id,
            'detail_url' => "/tasks/{$task->id}",
        ]);

        return response()->json($task);
    }

    /**
     * Delete task
     */
    public function destroy(Task $task)
    {
        $taskData = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'deleter_id' => auth()->id(),
            'deleter_name' => auth()->user()->name,
            'assignee_id' => $task->assignees->pluck('id')->toArray(),
            'creator_id' => $task->created_by,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Task',
            'model_id' => $task->id,
        ];

        $task->delete();

        // 🔔 Trigger notification
        $this->notificationService->trigger('task_deleted', $taskData);

        return response()->json(['message' => 'Task deleted successfully']);
    }
}

// ============================================
// 2. COMMENT CONTROLLER - Contoh Implementasi
// ============================================

class CommentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Add comment to task
     */
    public function store(Request $request, Task $task)
    {
        $comment = $task->comments()->create([
            'comment' => $request->comment,
            'created_by' => auth()->id(),
        ]);

        // 🔔 Trigger notification: comment_added
        $this->notificationService->trigger('comment_added', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'comment_id' => $comment->id,
            'comment_preview' => \Str::limit($comment->comment, 50),
            'commenter_id' => auth()->id(),
            'commenter_name' => auth()->user()->name,
            'assignee_id' => $task->assignees->pluck('id')->toArray(),
            'creator_id' => $task->created_by,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Comment',
            'model_id' => $comment->id,
            'detail_url' => "/tasks/{$task->id}#comment-{$comment->id}",
        ]);

        // 🔔 Check for mentions (@username)
        $mentions = $this->extractMentions($comment->comment);
        foreach ($mentions as $username) {
            $mentionedUser = User::where('username', $username)->first();
            if ($mentionedUser) {
                $this->notificationService->trigger('comment_mentioned', [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'comment_id' => $comment->id,
                    'assignee_id' => $mentionedUser->id,
                    'commenter_id' => auth()->id(),
                    'commenter_name' => auth()->user()->name,
                    'project_id' => $task->project_id,
                    'workspace_id' => $task->project->workspace_id,
                    'triggered_by' => auth()->id(),
                    'model_type' => 'Comment',
                    'model_id' => $comment->id,
                    'detail_url' => "/tasks/{$task->id}#comment-{$comment->id}",
                ]);
            }
        }

        return response()->json($comment);
    }

    /**
     * Extract @mentions from text
     */
    private function extractMentions(string $text): array
    {
        preg_match_all('/@(\w+)/', $text, $matches);
        return $matches[1] ?? [];
    }
}

// ============================================
// 3. PROJECT CONTROLLER - Contoh Implementasi
// ============================================

class ProjectController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create new project
     */
    public function store(Request $request)
    {
        $project = Project::create($request->validated());

        // 🔔 Trigger notification
        $this->notificationService->trigger('project_created', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'creator_id' => auth()->id(),
            'creator_name' => auth()->user()->name,
            'workspace_id' => $project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Project',
            'model_id' => $project->id,
            'detail_url' => "/projects/{$project->slug}",
        ]);

        return response()->json($project);
    }

    /**
     * Add member to project
     */
    public function addMember(Request $request, Project $project)
    {
        $userId = $request->user_id;
        $roleId = $request->project_role_id;
        
        $project->members()->attach($userId, [
            'project_role_id' => $roleId,
            'created_by' => auth()->id(),
        ]);

        $role = ProjectRole::find($roleId);

        // 🔔 Trigger notification
        $this->notificationService->trigger('project_member_added', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'assignee_id' => $userId,
            'role_name' => $role->name,
            'adder_id' => auth()->id(),
            'adder_name' => auth()->user()->name,
            'workspace_id' => $project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Project',
            'model_id' => $project->id,
            'detail_url' => "/projects/{$project->slug}",
        ]);

        return response()->json(['message' => 'Member added successfully']);
    }

    /**
     * Remove member from project
     */
    public function removeMember(Project $project, $userId)
    {
        $project->members()->detach($userId);

        // 🔔 Trigger notification
        $this->notificationService->trigger('project_member_removed', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'assignee_id' => $userId,
            'remover_id' => auth()->id(),
            'remover_name' => auth()->user()->name,
            'workspace_id' => $project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Project',
            'model_id' => $project->id,
        ]);

        return response()->json(['message' => 'Member removed successfully']);
    }
}

// ============================================
// 4. WORKSPACE CONTROLLER - Contoh Implementasi
// ============================================

class WorkspaceController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Invite user to workspace
     */
    public function inviteMember(Request $request, Workspace $workspace)
    {
        $userId = $request->user_id;
        
        // Create invitation logic...

        // 🔔 Trigger notification
        $this->notificationService->trigger('workspace_invitation', [
            'workspace_id' => $workspace->id,
            'workspace_name' => $workspace->name,
            'assignee_id' => $userId,
            'inviter_id' => auth()->id(),
            'inviter_name' => auth()->user()->name,
            'triggered_by' => auth()->id(),
            'model_type' => 'Workspace',
            'model_id' => $workspace->id,
            'detail_url' => "/workspaces/{$workspace->slug}",
        ]);

        return response()->json(['message' => 'Invitation sent']);
    }

    /**
     * Add member to workspace (after accept invitation)
     */
    public function addMember(Request $request, Workspace $workspace)
    {
        $userId = $request->user_id;
        $roleId = $request->workspace_role_id;
        
        $workspace->members()->attach($userId, [
            'workspace_role_id' => $roleId,
            'created_by' => auth()->id(),
        ]);

        $role = WorkspaceRole::find($roleId);

        // 🔔 Trigger notification
        $this->notificationService->trigger('workspace_member_added', [
            'workspace_id' => $workspace->id,
            'workspace_name' => $workspace->name,
            'assignee_id' => $userId,
            'role_name' => $role->name,
            'triggered_by' => auth()->id(),
            'model_type' => 'Workspace',
            'model_id' => $workspace->id,
            'detail_url' => "/workspaces/{$workspace->slug}",
        ]);

        return response()->json(['message' => 'Member added successfully']);
    }
}

// ============================================
// 5. ATTACHMENT CONTROLLER - Contoh Implementasi
// ============================================

class AttachmentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Upload file to task
     */
    public function store(Request $request, Task $task)
    {
        // File upload logic...
        $attachment = Attachment::create([
            'model_type' => 'Task',
            'model_id' => $task->id,
            'file_path' => $filePath,
            'original_filename' => $request->file->getClientOriginalName(),
            'created_by' => auth()->id(),
        ]);

        // 🔔 Trigger notification
        $this->notificationService->trigger('file_attached', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'file_name' => $attachment->original_filename,
            'uploader_id' => auth()->id(),
            'uploader_name' => auth()->user()->name,
            'assignee_id' => $task->assignees->pluck('id')->toArray(),
            'creator_id' => $task->created_by,
            'project_id' => $task->project_id,
            'workspace_id' => $task->project->workspace_id,
            'triggered_by' => auth()->id(),
            'model_type' => 'Attachment',
            'model_id' => $attachment->id,
            'detail_url' => "/tasks/{$task->id}",
        ]);

        return response()->json($attachment);
    }
}

// ============================================
// 6. SCHEDULED NOTIFICATIONS (Console Command)
// ============================================

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendDueDateNotifications extends Command
{
    protected $signature = 'notifications:due-dates';
    protected $description = 'Send notifications for tasks approaching due date';

    public function handle(NotificationService $notificationService)
    {
        // Tasks due tomorrow
        $tasksDueTomorrow = Task::whereDate('due_date', Carbon::tomorrow())
            ->whereHas('status', function($q) {
                $q->where('is_completed', false)
                  ->where('is_cancelled', false);
            })
            ->with(['assignees', 'project.workspace'])
            ->get();

        foreach ($tasksDueTomorrow as $task) {
            // 🔔 Trigger notification: task_due_date_approaching
            $notificationService->trigger('task_due_date_approaching', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'due_date' => $task->due_date->format('Y-m-d'),
                'assignee_id' => $task->assignees->pluck('id')->toArray(),
                'creator_id' => $task->created_by,
                'project_id' => $task->project_id,
                'workspace_id' => $task->project->workspace_id,
                'triggered_by' => 1, // System
                'model_type' => 'Task',
                'model_id' => $task->id,
                'detail_url' => "/tasks/{$task->id}",
            ]);
        }

        // Tasks overdue
        $tasksOverdue = Task::where('due_date', '<', Carbon::now())
            ->whereHas('status', function($q) {
                $q->where('is_completed', false)
                  ->where('is_cancelled', false);
            })
            ->with(['assignees', 'project.workspace'])
            ->get();

        foreach ($tasksOverdue as $task) {
            // 🔔 Trigger notification: task_overdue
            $notificationService->trigger('task_overdue', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'due_date' => $task->due_date->format('Y-m-d'),
                'assignee_id' => $task->assignees->pluck('id')->toArray(),
                'creator_id' => $task->created_by,
                'project_id' => $task->project_id,
                'workspace_id' => $task->project->workspace_id,
                'triggered_by' => 1, // System
                'model_type' => 'Task',
                'model_id' => $task->id,
                'detail_url' => "/tasks/{$task->id}",
            ]);
        }

        $this->info('Due date notifications sent successfully!');
    }
}

// Register di app/Console/Kernel.php:
// protected function schedule(Schedule $schedule)
// {
//     $schedule->command('notifications:due-dates')->daily();
// }
