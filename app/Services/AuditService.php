<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log audit activity for CREATE action
     */
    public function logCreated(Model $model, string $message = null, Request $request = null): AuditLog
    {
        return $this->logActivity(
            model: $model,
            action: 'created',
            before: null,
            after: $model->toArray(),
            message: $message ?: $this->generateMessage($model, 'created'),
            request: $request
        );
    }

    /**
     * Log audit activity for UPDATE action
     */
    public function logUpdated(Model $model, array $originalData, string $message = null, Request $request = null): AuditLog
    {
        return $this->logActivity(
            model: $model,
            action: 'updated',
            before: $originalData,
            after: $model->toArray(),
            message: $message ?: $this->generateMessage($model, 'updated'),
            request: $request
        );
    }

    /**
     * Log audit activity for DELETE action
     */
    public function logDeleted(Model $model, string $message = null, Request $request = null): AuditLog
    {
        return $this->logActivity(
            model: $model,
            action: 'deleted',
            before: $model->toArray(),
            after: null,
            message: $message ?: $this->generateMessage($model, 'deleted'),
            request: $request
        );
    }

    /**
     * Log audit activity for RESTORE action (soft delete)
     */
    public function logRestored(Model $model, string $message = null, Request $request = null): AuditLog
    {
        return $this->logActivity(
            model: $model,
            action: 'restored',
            before: null,
            after: $model->toArray(),
            message: $message ?: $this->generateMessage($model, 'restored'),
            request: $request
        );
    }

    /**
     * Log custom audit activity
     */
    public function logCustom(
        Model $model, 
        string $action, 
        array $before = null, 
        array $after = null, 
        string $message = null, 
        Request $request = null
    ): AuditLog {
        return $this->logActivity(
            model: $model,
            action: $action,
            before: $before,
            after: $after,
            message: $message ?: $this->generateMessage($model, $action),
            request: $request
        );
    }

    /**
     * Main method to log audit activity
     */
    private function logActivity(
        Model $model,
        string $action,
        array $before = null,
        array $after = null,
        string $message = null,
        Request $request = null
    ): AuditLog {
        $user = Auth::user();
        $request = $request ?: request();

        return AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'before' => $before ? $this->sanitizeData($before) : null,
            'after' => $after ? $this->sanitizeData($after) : null,
            'message' => $message,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ]);
    }

    /**
     * Generate default message for audit log
     */
    private function generateMessage(Model $model, string $action): string
    {
        $modelName = $this->getModelDisplayName($model);
        $identifier = $this->getModelIdentifier($model);
        
        $messages = [
            'created' => "Membuat {$modelName} baru: {$identifier}",
            'updated' => "Memperbarui {$modelName}: {$identifier}",
            'deleted' => "Menghapus {$modelName}: {$identifier}",
            'restored' => "Memulihkan {$modelName}: {$identifier}",
        ];

        return $messages[$action] ?? "Melakukan aksi '{$action}' pada {$modelName}: {$identifier}";
    }

    /**
     * Get display name for model
     */
    private function getModelDisplayName(Model $model): string
    {
        $displayNames = [
            'Workspace' => 'workspace',
            'Project' => 'project',
            'Task' => 'task',
            'User' => 'user',
            'Comment' => 'komentar',
            'Attachment' => 'attachment',
            'Notification' => 'notifikasi',
            'WorkspaceUser' => 'anggota workspace',
            'ProjectUser' => 'anggota project',
        ];

        $className = class_basename($model);
        return $displayNames[$className] ?? strtolower($className);
    }

    /**
     * Get identifier for model (name, title, or id)
     */
    private function getModelIdentifier(Model $model): string
    {
        if (isset($model->name)) {
            return $model->name;
        }
        
        if (isset($model->title)) {
            return $model->title;
        }

        if (isset($model->email)) {
            return $model->email;
        }

        if (isset($model->slug)) {
            return $model->slug;
        }

        return "ID #{$model->id}";
    }

    /**
     * Sanitize sensitive data before storing
     */
    private function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[HIDDEN]';
            }
        }

        // Remove timestamps untuk mengurangi noise jika tidak diperlukan
        unset($data['created_at'], $data['updated_at']);

        return $data;
    }

    /**
     * Get changes between before and after data
     */
    public function getChanges(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                $changes[$key] = [
                    'old' => $before[$key] ?? null,
                    'new' => $value
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if model should be audited (can be extended)
     */
    public function shouldAudit(Model $model): bool
    {
        // Default: audit semua model
        // Bisa ditambahkan logic untuk skip model tertentu
        return true;
    }

    /**
     * Log bulk operations
     */
    public function logBulkOperation(
        string $modelType,
        array $modelIds,
        string $action,
        string $message,
        Request $request = null
    ): void {
        $user = Auth::user();
        $request = $request ?: request();

        $logData = [
            'user_id' => $user ? $user->id : null,
            'model_type' => $modelType,
            'action' => $action,
            'message' => $message,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $logs = [];
        foreach ($modelIds as $modelId) {
            $logs[] = array_merge($logData, ['model_id' => $modelId]);
        }

        AuditLog::insert($logs);
    }

    /**
     * Get audit logs for specific model
     */
    public function getModelAuditLogs(Model $model, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::with('user')
            ->where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent audit logs for user
     */
    public function getUserAuditLogs(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::with('user')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
