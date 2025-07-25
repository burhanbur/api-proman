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
                Route::get('/{id}', [UserController::class, 'show']);
                Route::put('/{id}', [UserController::class, 'update']);
                Route::delete('/{id}', [UserController::class, 'destroy']);
            });

            // Workspace
            Route::group(['prefix' => 'workspaces'], function () {
                Route::post('/', [WorkspaceController::class, 'store']);
                Route::get('/{slug}', [WorkspaceController::class, 'show']);
                Route::put('/{slug}', [WorkspaceController::class, 'update']);
                Route::delete('/{slug}', [WorkspaceController::class, 'destroy']);
            });
        });

        Route::get('workspaces', [WorkspaceController::class, 'index']);

        // Project
        Route::group(['prefix' => 'projects'], function () {
            Route::get('/', [ProjectController::class, 'index']);
            Route::post('/', [ProjectController::class, 'store']);
            Route::get('/{project}', [ProjectController::class, 'show']);
            Route::put('/{project}', [ProjectController::class, 'update']);
            Route::delete('/{project}', [ProjectController::class, 'destroy']);
        });

        // Task
        Route::group(['prefix' => 'tasks'], function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/', [TaskController::class, 'store']);
            Route::get('/{task}', [TaskController::class, 'show']);
            Route::put('/{task}', [TaskController::class, 'update']);
            Route::delete('/{task}', [TaskController::class, 'destroy']);
        });

        // Comment
        Route::group(['prefix' => 'comments'], function () {
            Route::get('/', [CommentController::class, 'index']);
            Route::post('/', [CommentController::class, 'store']);
            Route::get('/{comment}', [CommentController::class, 'show']);
            Route::put('/{comment}', [CommentController::class, 'update']);
            Route::delete('/{comment}', [CommentController::class, 'destroy']);
        });

        // Attachment
        Route::group(['prefix' => 'attachments'], function () {
            Route::get('/', [AttachmentController::class, 'index']);
            Route::post('/', [AttachmentController::class, 'store']);
            Route::get('/{attachment}', [AttachmentController::class, 'show']);
            Route::put('/{attachment}', [AttachmentController::class, 'update']);
            Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
        });

        // Notification (tanpa softdelete)
        Route::group(['prefix' => 'notifications'], function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/', [NotificationController::class, 'store']);
            Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
            Route::put('/{notification}', [NotificationController::class, 'updateReadStatus']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });
    });
});