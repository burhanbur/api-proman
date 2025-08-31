<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkspaceController;

Route::group(['middleware' => ['cors']], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');
        Route::get('me', [UserController::class, 'me'])->middleware('auth');
    });

    Route::group(['middleware' => ['auth']], function () {
        Route::group(['middleware' => ['role:admin']], function () {
            // User Management
            Route::group(['prefix' => 'users'], function () {
                Route::get('/', [UserController::class, 'index']);
                Route::post('/', [UserController::class, 'store']);
                Route::get('/{uuid}', [UserController::class, 'show']);
                Route::put('/{uuid}/password', [UserController::class, 'changeMyPassword']);
                Route::put('/{uuid}', [UserController::class, 'update']);
                Route::delete('/{uuid}', [UserController::class, 'destroy']);
            });
        });

        // Workspace
        Route::group(['prefix' => 'workspaces'], function () {
            Route::get('/', [WorkspaceController::class, 'index']);
            Route::post('/', [WorkspaceController::class, 'store']);
            Route::get('/{slug}', [WorkspaceController::class, 'show']);
            Route::put('/{slug}', [WorkspaceController::class, 'update']);
            Route::delete('/{slug}', [WorkspaceController::class, 'destroy']);

            Route::post('/{slug}/users', [WorkspaceController::class, 'storeUser']);
            Route::put('/{slug}/users', [WorkspaceController::class, 'updateUser']);
            Route::delete('/{slug}/users', [WorkspaceController::class, 'destroyUser']);
        });

        // Project
        Route::group(['prefix' => 'projects'], function () {
            Route::get('/', [ProjectController::class, 'index']);
            Route::post('/', [ProjectController::class, 'store']);
            Route::get('/{slug}', [ProjectController::class, 'show']);
            Route::put('/{slug}', [ProjectController::class, 'update']);
            Route::delete('/{slug}', [ProjectController::class, 'destroy']);
        });

        // Task
        Route::group(['prefix' => 'tasks'], function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/', [TaskController::class, 'store']);
            Route::get('/{uuid}', [TaskController::class, 'show']);
            Route::put('/{uuid}', [TaskController::class, 'update']);
            Route::delete('/{uuid}', [TaskController::class, 'destroy']);
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
    });
});