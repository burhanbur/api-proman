<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PriorityController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectRoleController;
use App\Http\Controllers\Api\ProjectStatusController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TemplateStatusController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceRoleController;

Route::group(['middleware' => ['cors']], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');
        Route::get('me', [AuthController::class, 'me'])->middleware('auth');
    });

    Route::group(['middleware' => ['auth']], function () {
        // Route::group(['middleware' => ['role:admin']], function () {
            // User Management
            Route::group(['prefix' => 'users'], function () {
                Route::get('/', [UserController::class, 'index']);
                Route::post('/', [UserController::class, 'store']);
                Route::get('/{uuid}', [UserController::class, 'show']);
                Route::put('/{uuid}/password', [UserController::class, 'changeMyPassword']);
                Route::put('/{uuid}', [UserController::class, 'update']);
                Route::delete('/{uuid}', [UserController::class, 'destroy']);
            });
        // });

        // Workspace
        Route::group(['prefix' => 'workspaces'], function () {
            Route::get('/', [WorkspaceController::class, 'index']);
            Route::post('/', [WorkspaceController::class, 'store']);
            Route::get('/{slug}', [WorkspaceController::class, 'show']);
            Route::put('/{slug}', [WorkspaceController::class, 'update']);
            Route::post('/{slug}/remove-logo', [WorkspaceController::class, 'removeLogo']);
            Route::delete('/{slug}', [WorkspaceController::class, 'destroy']);

            Route::post('/{slug}/users', [WorkspaceController::class, 'storeUser']);
            Route::put('/{slug}/users', [WorkspaceController::class, 'updateUser']);
            Route::delete('/{slug}/users', [WorkspaceController::class, 'destroyUser']);

            Route::get('/{slug}/activities', [WorkspaceController::class, 'getActivities']);
        });

        // Project
        Route::group(['prefix' => 'projects'], function () {
            Route::get('/', [ProjectController::class, 'index']);
            Route::post('/', [ProjectController::class, 'store']);
            Route::get('/{slug}', [ProjectController::class, 'show']);
            Route::put('/{slug}', [ProjectController::class, 'update']);
            Route::delete('/{slug}', [ProjectController::class, 'destroy']);

            Route::post('/{slug}/users', [ProjectController::class, 'storeUser']);
            Route::put('/{slug}/users', [ProjectController::class, 'updateUser']);
            Route::delete('/{slug}/users', [ProjectController::class, 'destroyUser']);

            Route::get('/{slug}/status', [ProjectController::class, 'getProjectStatus']);
            Route::post('/{slug}/status', [ProjectController::class, 'storeProjectStatus']);

            // Make literal 'order' route come before the parameterized route to avoid
            // Laravel capturing the word 'order' as {statusId}.
            Route::put('/{slug}/status/order', [ProjectController::class, 'updateStatusOrder']);
            Route::put('/{slug}/status/{statusId}', [ProjectController::class, 'updateProjectStatus'])
                ->where('statusId', '[0-9]+');

            Route::delete('/{slug}/status/{statusId}', [ProjectController::class, 'deleteProjectStatus']);

            Route::get('/{slug}/attachments', [ProjectController::class, 'getProjectAttachments']);
            Route::get('/{slug}/activities', [ProjectController::class, 'getActivities']);
        });

        // Task
        Route::group(['prefix' => 'tasks'], function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/', [TaskController::class, 'store']);
            Route::get('/recent', [TaskController::class, 'recent']);

            Route::get('/{uuid}', [TaskController::class, 'show'])
                ->where('uuid', '[0-9a-fA-F\-]{36}');
            Route::put('/{uuid}', [TaskController::class, 'update']);
            Route::delete('/{uuid}', [TaskController::class, 'destroy']);

            Route::put('/{uuid}/status', [TaskController::class, 'updateStatus']);
            Route::post('/{uuid}/assign', [TaskController::class, 'assignTask']);
            Route::delete('/{uuid}/assign/{userId}', [TaskController::class, 'unassignTask']);
        });

        // Comment
        Route::group(['prefix' => 'comments'], function () {
            Route::get('/', [CommentController::class, 'index']);
            Route::post('/', [CommentController::class, 'store']);
            Route::get('/{uuid}', [CommentController::class, 'show']);
            Route::put('/{uuid}', [CommentController::class, 'update']);
            Route::delete('/{uuid}', [CommentController::class, 'destroy']);
        });

        // Attachment
        Route::group(['prefix' => 'attachments'], function () {
            Route::get('/', [AttachmentController::class, 'index']);
            Route::post('/', [AttachmentController::class, 'store']);
            Route::get('/{model_type}/{model_id}/{uuid}/download', [AttachmentController::class, 'download']);
            Route::get('/{uuid}', [AttachmentController::class, 'show']);
            Route::put('/{uuid}', [AttachmentController::class, 'update']);
            Route::delete('/{uuid}', [AttachmentController::class, 'destroy']);
        });

        // Notification (tanpa softdelete)
        Route::group(['prefix' => 'notifications'], function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/', [NotificationController::class, 'store']);
            Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
            Route::put('/{uuid}', [NotificationController::class, 'updateReadStatus']);
            Route::delete('/{uuid}', [NotificationController::class, 'destroy']);
        });

        // Project Status
        Route::group(['prefix' => 'project-status'], function () {
            Route::get('/', [ProjectStatusController::class, 'index']);
            Route::post('/', [ProjectStatusController::class, 'store']);
            Route::get('/{id}', [ProjectStatusController::class, 'show']);
            Route::put('/{id}', [ProjectStatusController::class, 'update']);
            Route::delete('/{id}', [ProjectStatusController::class, 'destroy']);
        });

        // Project Role
        Route::group(['prefix' => 'project-roles'], function () {
            Route::get('/', [ProjectRoleController::class, 'index']);
            Route::post('/', [ProjectRoleController::class, 'store']);
            Route::get('/{id}', [ProjectRoleController::class, 'show']);
            Route::put('/{id}', [ProjectRoleController::class, 'update']);
            Route::delete('/{id}', [ProjectRoleController::class, 'destroy']);

            Route::get('/{slug}/roles/dropdown', [ProjectRoleController::class, 'dropdown']);
        });

        // Priority
        Route::group(['prefix' => 'priorities'], function () {
            Route::get('/', [PriorityController::class, 'index']);
            Route::post('/', [PriorityController::class, 'store']);
            Route::get('/{id}', [PriorityController::class, 'show']);
            Route::put('/{id}', [PriorityController::class, 'update']);
            Route::delete('/{id}', [PriorityController::class, 'destroy']);
        });

        // Template Status
        Route::group(['prefix' => 'template-status'], function () {
            Route::get('/', [TemplateStatusController::class, 'index']);
            Route::post('/', [TemplateStatusController::class, 'store']);
            Route::get('/{id}', [TemplateStatusController::class, 'show']);
            Route::put('/{id}', [TemplateStatusController::class, 'update']);
            Route::delete('/{id}', [TemplateStatusController::class, 'destroy']);
        });

        // Workspace Role
        Route::group(['prefix' => 'workspace-roles'], function () {
            Route::get('/', [WorkspaceRoleController::class, 'index']);
            Route::get('/{slug}/roles/dropdown', [WorkspaceRoleController::class, 'dropdown']);
            Route::get('/{id}', [WorkspaceRoleController::class, 'show'])->where('id', '[0-9]+');
            Route::post('/', [WorkspaceRoleController::class, 'store']);
            Route::put('/{id}', [WorkspaceRoleController::class, 'update']);
            Route::delete('/{id}', [WorkspaceRoleController::class, 'destroy']);
        });

        Route::get('audit-logs', [AuditLogController::class, 'index']);
        
        // Notes
        Route::group(['prefix' => 'notes'], function () {
            Route::post('/', [NoteController::class, 'store']);
                Route::get('/', [NoteController::class, 'index']);
                Route::get('/{id}', [NoteController::class, 'show']);
                Route::put('/{id}', [NoteController::class, 'update']);
                Route::delete('/{id}', [NoteController::class, 'destroy']);
            });
    });
});