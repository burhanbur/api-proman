# 🔔 Notification System Documentation

Sistem notifikasi yang modular dan konfigurable untuk aplikasi ProMan.

## 📊 Database Schema

### Tabel Utama

1. **notification_events** - Daftar semua event yang bisa trigger notifikasi
2. **notification_templates** - Template notifikasi untuk setiap event
3. **user_notification_preferences** - Preferensi notifikasi per user
4. **notification_event_configs** - Konfigurasi notifikasi per workspace/project
5. **notifications** - Data notifikasi yang dikirim ke user

## 🚀 Setup & Installation

### 1. Jalankan Migration

```bash
php artisan migrate
```

### 2. Jalankan Seeder

```bash
# Seed event definitions
php artisan db:seed --class=NotificationEventSeeder

# Seed notification templates
php artisan db:seed --class=NotificationTemplateSeeder

# Seed default global configs
php artisan db:seed --class=NotificationEventConfigSeeder
```

Atau sekaligus:

```bash
php artisan db:seed --class=NotificationEventSeeder && \
php artisan db:seed --class=NotificationTemplateSeeder && \
php artisan db:seed --class=NotificationEventConfigSeeder
```

## 💻 Cara Penggunaan

### Trigger Notifikasi

Di controller atau service Anda, trigger notifikasi seperti ini:

```php
use App\Services\NotificationService;

// Contoh: Di TaskController ketika assign task
public function assignTask(Request $request, Task $task)
{
    $assigneeId = $request->assignee_id;
    
    // Save task assignment
    $task->assignees()->attach($assigneeId, [
        'assigned_by' => auth()->id(),
    ]);
    
    // 🔔 Trigger notification
    app(NotificationService::class)->trigger('task_assigned', [
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
    
    return response()->json($task);
}
```

### Contoh Trigger untuk Event Lain

#### Task Status Changed
```php
app(NotificationService::class)->trigger('task_status_changed', [
    'task_id' => $task->id,
    'task_title' => $task->title,
    'old_status' => $oldStatus->name,
    'new_status' => $newStatus->name,
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
```

#### Comment Added
```php
app(NotificationService::class)->trigger('comment_added', [
    'task_id' => $task->id,
    'task_title' => $task->title,
    'comment_preview' => Str::limit($comment->comment, 50),
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
```

#### Project Member Added
```php
app(NotificationService::class)->trigger('project_member_added', [
    'project_id' => $project->id,
    'project_name' => $project->name,
    'assignee_id' => $newMemberId, // User yang ditambahkan
    'role_name' => $role->name,
    'adder_name' => auth()->user()->name,
    'workspace_id' => $project->workspace_id,
    'triggered_by' => auth()->id(),
    'model_type' => 'Project',
    'model_id' => $project->id,
    'detail_url' => "/projects/{$project->slug}",
]);
```

## ⚙️ Konfigurasi Notifikasi

### Global Config (Default untuk semua workspace/project)

Sudah di-seed secara otomatis. Bisa diubah via admin panel.

### Project-Level Config

Admin/Project Manager bisa override config untuk project tertentu:

```php
use App\Models\NotificationEventConfig;
use App\Models\NotificationEvent;

// Disable notification untuk comment_added di project tertentu
$event = NotificationEvent::where('code', 'comment_added')->first();

NotificationEventConfig::create([
    'notification_event_id' => $event->id,
    'project_id' => $projectId,
    'workspace_id' => null,
    'is_enabled' => false, // ❌ Disable untuk project ini
    'notify_assignee' => true,
    'notify_creator' => true,
    'notify_project_members' => false,
    'notify_workspace_members' => false,
    'created_by' => auth()->id(),
]);
```

### Workspace-Level Config

```php
// Untuk workspace tertentu, task_assigned notify semua member
NotificationEventConfig::create([
    'notification_event_id' => $event->id,
    'workspace_id' => $workspaceId,
    'project_id' => null, // NULL = workspace level
    'is_enabled' => true,
    'notify_assignee' => true,
    'notify_creator' => true,
    'notify_project_members' => true, // ✅ Semua member project
    'notify_workspace_members' => false,
    'created_by' => auth()->id(),
]);
```

### Dengan Conditions (Advanced)

```php
// Hanya notify untuk task dengan priority HIGH atau URGENT
NotificationEventConfig::create([
    'notification_event_id' => $event->id,
    'project_id' => $projectId,
    'is_enabled' => true,
    'notify_assignee' => true,
    'notify_creator' => false,
    'notify_project_members' => false,
    'notify_workspace_members' => false,
    'conditions' => [
        'priority_level' => ['high', 'urgent'],
    ],
    'created_by' => auth()->id(),
]);
```

## 👤 User Preferences

User bisa mengatur notifikasi apa yang mau diterima:

```php
use App\Services\NotificationService;

// Set preference untuk user
$notificationService = app(NotificationService::class);

$notificationService->setUserPreference(
    userId: $userId,
    eventId: $eventId,
    preferences: [
        'is_enabled' => true,
        'channel_email' => false,    // Tidak via email
        'channel_push' => true,       // Via push notification
        'channel_in_app' => true,     // Via in-app notification
    ]
);

// Get user preferences
$preferences = $notificationService->getUserPreferences($userId);
```

## 📖 Cara Kerja Sistem

### Flow Lengkap

```
1. Action terjadi (misal: user A assign task ke user B)
   ↓
2. Controller/Service trigger event "task_assigned"
   ↓
3. NotificationService cek:
   - Apakah event aktif?
   - Apakah ada config untuk workspace/project ini?
   - Apakah config enabled?
   - Apakah conditions terpenuhi?
   ↓
4. Tentukan recipients berdasarkan config:
   - notify_assignee → user B
   - notify_creator → user yang buat task
   - notify_project_members → semua anggota project
   - notify_workspace_members → semua anggota workspace
   ↓
5. Filter berdasarkan user preferences:
   - User C disable notifikasi ini → skip
   - User D enable → lanjut
   ↓
6. Generate notifikasi dari template:
   - Replace {{variable}} dengan data actual
   ↓
7. Insert ke tabel notifications
   ↓
8. User menerima notifikasi
```

### Priority Config

System akan mencari config dengan priority:

1. **Project config** (paling spesifik)
2. **Workspace config** (lebih umum)
3. **Global config** (fallback default)

## 📝 Available Events

### Task Events
- `task_created` - Task baru dibuat
- `task_assigned` - Task di-assign ke user
- `task_unassigned` - User di-remove dari task
- `task_status_changed` - Status task berubah
- `task_priority_changed` - Priority task berubah
- `task_due_date_approaching` - Due date mendekati (1 hari sebelumnya)
- `task_overdue` - Task melewati due date
- `task_completed` - Task selesai
- `task_deleted` - Task dihapus

### Comment Events
- `comment_added` - Comment baru ditambahkan
- `comment_mentioned` - User di-mention (@username)
- `comment_replied` - Reply pada comment

### Project Events
- `project_created` - Project baru dibuat
- `project_member_added` - Member ditambahkan ke project
- `project_member_removed` - Member di-remove dari project
- `project_role_changed` - Role member berubah

### Workspace Events
- `workspace_invitation` - Invitation ke workspace
- `workspace_member_added` - Member bergabung ke workspace
- `workspace_member_removed` - Member di-remove dari workspace
- `workspace_role_changed` - Role member berubah

### Attachment Events
- `file_attached` - File dilampirkan ke task

## 🎨 Template Variables

Setiap event punya template dengan placeholder yang bisa digunakan:

```
{{task_title}} - Judul task
{{task_id}} - ID task
{{assignee_id}} - ID user yang di-assign
{{assigner_name}} - Nama user yang assign
{{creator_name}} - Nama user yang buat
{{project_name}} - Nama project
{{workspace_name}} - Nama workspace
{{old_status}} - Status lama
{{new_status}} - Status baru
{{due_date}} - Due date
{{comment_preview}} - Preview comment
... dan lain-lain
```

## 🔧 API Endpoints (Contoh)

### Get User Notifications
```php
// NotificationController.php
public function index(Request $request)
{
    $notificationService = app(NotificationService::class);
    
    $notifications = $notificationService->getUserNotifications(
        userId: auth()->id(),
        limit: $request->get('limit', 20),
        unreadOnly: $request->get('unread_only', false)
    );
    
    return response()->json($notifications);
}
```

### Get Unread Count
```php
public function unreadCount()
{
    $count = app(NotificationService::class)->getUnreadCount(auth()->id());
    
    return response()->json(['count' => $count]);
}
```

### Mark as Read
```php
public function markAsRead($notificationId)
{
    $success = app(NotificationService::class)->markAsRead(
        notificationId: $notificationId,
        userId: auth()->id()
    );
    
    return response()->json(['success' => $success]);
}
```

### Mark All as Read
```php
public function markAllAsRead()
{
    $count = app(NotificationService::class)->markAllAsRead(auth()->id());
    
    return response()->json(['updated' => $count]);
}
```

## 🎯 Best Practices

1. **Selalu gunakan event codes yang sudah didefinisikan** di NotificationEventSeeder
2. **Kirim data yang lengkap** saat trigger notifikasi (agar template bisa di-render dengan baik)
3. **Gunakan conditions** untuk filtering yang lebih spesifik
4. **Test notifikasi** di environment development dulu
5. **Monitor performance** jika ada project dengan banyak member
6. **Queue notifications** untuk performa yang lebih baik (opsional)

## 🚧 Future Enhancements

- [ ] Email notification channel
- [ ] Push notification channel (FCM/APNS)
- [ ] SMS notification channel
- [ ] Notification scheduling
- [ ] Digest notifications (summary per hari/minggu)
- [ ] Real-time notifications via WebSocket/Pusher
- [ ] Notification history/archive
- [ ] Admin panel untuk manage events & templates

## 📞 Support

Jika ada pertanyaan atau issue, silakan hubungi tim development.

---

**Created with ❤️ by ProMan Development Team**
