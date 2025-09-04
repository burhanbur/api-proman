<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HasAuditLog
{
    /**
     * Get audit service instance
     */
    protected function auditService(): AuditService
    {
        return AuditService::getInstance();
    }

    /**
     * Log created model
     */
    protected function auditCreated(Model $model, string $message = null, Request $request = null): void
    {
        $this->auditService()->logCreated($model, $message, $request);
    }

    /**
     * Log updated model
     */
    protected function auditUpdated(Model $model, array $originalData, string $message = null, Request $request = null): void
    {
        $this->auditService()->logUpdated($model, $originalData, $message, $request);
    }

    /**
     * Log deleted model
     */
    protected function auditDeleted(Model $model, string $message = null, Request $request = null): void
    {
        $this->auditService()->logDeleted($model, $message, $request);
    }

    /**
     * Log restored model
     */
    protected function auditRestored(Model $model, string $message = null, Request $request = null): void
    {
        $this->auditService()->logRestored($model, $message, $request);
    }

    /**
     * Log custom action
     */
    protected function auditCustom(
        Model $model, 
        string $action, 
        array $before = null, 
        array $after = null, 
        string $message = null, 
        Request $request = null
    ): void {
        $this->auditService()->logCustom($model, $action, $before, $after, $message, $request);
    }

    /**
     * Log bulk operation
     */
    protected function auditBulk(
        string $modelType,
        array $modelIds,
        string $action,
        string $message,
        Request $request = null
    ): void {
        $this->auditService()->logBulkOperation($modelType, $modelIds, $action, $message, $request);
    }
}
