<?php 

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\NotificationEventConfig;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService {
    
    /**
     * Trigger notifikasi berdasarkan event
     * 
     * @param string $eventCode - kode event (e.g., 'task_assigned')
     * @param array $data - data context
     * @example trigger('task_assigned', [
     *     'task_id' => 123,
     *     'task_title' => 'Fix bug',
     *     'assignee_id' => 456,
     *     'assigner_id' => 789,
     *     'assigner_name' => 'John Doe',
     *     'creator_id' => 100,
     *     'project_id' => 10,
     *     'workspace_id' => 5,
     *     'triggered_by' => 789,
     *     'model_type' => 'Task',
     *     'model_id' => 123,
     *     'detail_url' => '/tasks/123'
     * ])
     * @return void
     */
    public function trigger(string $eventCode, array $data): void
    {
        try {
            // 1. Ambil event definition
            $event = NotificationEvent::where('code', $eventCode)
                ->where('is_active', true)
                ->first();
            
            if (!$event) {
                \Log::warning("Notification event not found or inactive: {$eventCode}");
                return;
            }
            
            // 2. Cek konfigurasi untuk workspace/project ini
            $config = $this->getEventConfig($event->id, $data);
            
            if (!$config || !$config->is_enabled) {
                \Log::info("Notification disabled for event: {$eventCode}");
                return;
            }
            
            // 3. Cek conditions (jika ada)
            if (!$config->matchesConditions($data)) {
                \Log::info("Notification conditions not met for event: {$eventCode}");
                return;
            }
            
            // 4. Tentukan siapa yang harus dapat notifikasi
            $recipients = $this->determineRecipients($config, $data);
            
            if (empty($recipients)) {
                \Log::info("No recipients for event: {$eventCode}");
                return;
            }
            
            // 5. Filter berdasarkan user preferences
            $recipients = $this->filterByUserPreferences($recipients, $event->id);
            
            if (empty($recipients)) {
                \Log::info("All recipients opted out for event: {$eventCode}");
                return;
            }
            
            // 6. Generate dan kirim notifikasi
            $this->sendNotifications($recipients, $event, $data);
            
            \Log::info("Notifications sent for event: {$eventCode} to " . count($recipients) . " recipients");
            
        } catch (\Exception $e) {
            \Log::error("Error triggering notification for event {$eventCode}: " . $e->getMessage());
        }
    }
    
    /**
     * Ambil konfigurasi notifikasi untuk event tertentu
     * Priority: Project config > Workspace config > Global config
     */
    private function getEventConfig(int $eventId, array $data): ?NotificationEventConfig
    {
        // 1. Cari config di level project (paling spesifik)
        if (isset($data['project_id'])) {
            $config = NotificationEventConfig::forEvent($eventId)
                ->forProject($data['project_id'])
                ->enabled()
                ->first();
            
            if ($config) {
                return $config;
            }
        }
        
        // 2. Kalau gak ada, cari di level workspace
        if (isset($data['workspace_id'])) {
            $config = NotificationEventConfig::forEvent($eventId)
                ->forWorkspace($data['workspace_id'])
                ->whereNull('project_id')
                ->enabled()
                ->first();
            
            if ($config) {
                return $config;
            }
        }
        
        // 3. Kalau masih gak ada, pakai global config (default)
        $config = NotificationEventConfig::forEvent($eventId)
            ->global()
            ->enabled()
            ->first();
        
        return $config;
    }
    
    /**
     * Tentukan siapa saja yang harus dapat notifikasi
     * berdasarkan config rules
     */
    private function determineRecipients(NotificationEventConfig $config, array $data): array
    {
        $recipients = [];
        
        // 1. Notify assignee (yang di-assign task)
        if ($config->notify_assignee && isset($data['assignee_id'])) {
            if (is_array($data['assignee_id'])) {
                $recipients = array_merge($recipients, $data['assignee_id']);
            } else {
                $recipients[] = $data['assignee_id'];
            }
        }
        
        // 2. Notify creator (yang buat task)
        if ($config->notify_creator && isset($data['creator_id'])) {
            $recipients[] = $data['creator_id'];
        }
        
        // 3. Notify semua anggota project
        if ($config->notify_project_members && isset($data['project_id'])) {
            $projectMembers = DB::table('project_users')
                ->where('project_id', $data['project_id'])
                ->pluck('user_id')
                ->toArray();
            
            $recipients = array_merge($recipients, $projectMembers);
        }
        
        // 4. Notify semua anggota workspace
        if ($config->notify_workspace_members && isset($data['workspace_id'])) {
            $workspaceMembers = DB::table('workspace_users')
                ->where('workspace_id', $data['workspace_id'])
                ->pluck('user_id')
                ->toArray();
            
            $recipients = array_merge($recipients, $workspaceMembers);
        }
        
        // 5. Hilangkan duplikat dan orang yang trigger action
        $recipients = array_unique($recipients);
        $recipients = array_diff($recipients, [$data['triggered_by'] ?? null]);
        
        return array_values(array_filter($recipients));
    }
    
    /**
     * Filter recipients berdasarkan user preferences
     */
    private function filterByUserPreferences(array $userIds, int $eventId): array
    {
        // Ambil preferences untuk event ini
        $preferences = UserNotificationPreference::forEvent($eventId)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');
        
        $filteredRecipients = [];
        
        foreach ($userIds as $userId) {
            // Jika user punya preference
            if (isset($preferences[$userId])) {
                $preference = $preferences[$userId];
                
                // Cek apakah user mau terima notifikasi ini
                if ($preference->is_enabled && $preference->channel_in_app) {
                    $filteredRecipients[] = $userId;
                }
            } else {
                // Jika user belum set preference, default terima notifikasi
                $filteredRecipients[] = $userId;
            }
        }
        
        return $filteredRecipients;
    }
    
    /**
     * Kirim notifikasi ke semua recipients
     */
    private function sendNotifications(array $userIds, NotificationEvent $event, array $data): void
    {
        // Ambil template untuk event ini
        $template = $event->activeTemplate();
        
        if (!$template) {
            \Log::warning("No active template found for event: {$event->code}");
            return;
        }
        
        foreach ($userIds as $userId) {
            try {
                Notification::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $userId,
                    'notification_event_id' => $event->id,
                    'related_model_type' => $data['model_type'] ?? null,
                    'related_model_id' => $data['model_id'] ?? null,
                    'triggered_by' => $data['triggered_by'] ?? null,
                    'type' => $template->type,
                    'title' => $template->renderTitle($data),
                    'message' => $template->renderMessage($data),
                    'detail_url' => $data['detail_url'] ?? null,
                    'is_read' => false,
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to create notification for user {$userId}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();
        
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        
        return false;
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }
    
    /**
     * Get notifications for a user
     */
    public function getUserNotifications(int $userId, int $limit = 20, bool $unreadOnly = false)
    {
        $query = Notification::forUser($userId)
            ->with(['event', 'triggeredBy'])
            ->orderBy('created_at', 'desc');
        
        if ($unreadOnly) {
            $query->unread();
        }
        
        return $query->limit($limit)->get();
    }
    
    /**
     * Create or update user notification preference
     */
    public function setUserPreference(int $userId, int $eventId, array $preferences): UserNotificationPreference
    {
        return UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'notification_event_id' => $eventId,
            ],
            $preferences
        );
    }
    
    /**
     * Get user notification preferences
     */
    public function getUserPreferences(int $userId)
    {
        return UserNotificationPreference::forUser($userId)
            ->with('event')
            ->get();
    }
}