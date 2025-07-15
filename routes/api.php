<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;


Route::group(['middleware' => ['cors']], function () {

    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');
    });

    Route::group(['middleware' => ['auth']], function () {
        // Workspace
        Route::group(['prefix' => 'workspace'], function () {
            Route::get('/', [WorkspaceController::class, 'index']);
            Route::post('/', [WorkspaceController::class, 'store']);
            Route::get('/{slug}', [WorkspaceController::class, 'show']);
            Route::put('/{slug}', [WorkspaceController::class, 'update']);
            Route::delete('/{slug}', [WorkspaceController::class, 'destroy']);
        });

        // Project
        Route::group(['prefix' => 'project'], function () {
            Route::get('/', [ProjectController::class, 'index']);
            Route::post('/', [ProjectController::class, 'store']);
            Route::get('/{project}', [ProjectController::class, 'show']);
            Route::put('/{project}', [ProjectController::class, 'update']);
            Route::delete('/{project}', [ProjectController::class, 'destroy']);
        });

        // Task
        Route::group(['prefix' => 'task'], function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/', [TaskController::class, 'store']);
            Route::get('/{task}', [TaskController::class, 'show']);
            Route::put('/{task}', [TaskController::class, 'update']);
            Route::delete('/{task}', [TaskController::class, 'destroy']);
        });

        // Comment
        Route::group(['prefix' => 'comment'], function () {
            Route::get('/', [CommentController::class, 'index']);
            Route::post('/', [CommentController::class, 'store']);
            Route::get('/{comment}', [CommentController::class, 'show']);
            Route::put('/{comment}', [CommentController::class, 'update']);
            Route::delete('/{comment}', [CommentController::class, 'destroy']);
        });

        // Attachment
        Route::group(['prefix' => 'attachment'], function () {
            Route::get('/', [AttachmentController::class, 'index']);
            Route::post('/', [AttachmentController::class, 'store']);
            Route::get('/{attachment}', [AttachmentController::class, 'show']);
            Route::put('/{attachment}', [AttachmentController::class, 'update']);
            Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
        });

        // Notification (tanpa softdelete)
        Route::group(['prefix' => 'notification'], function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/', [NotificationController::class, 'store']);
            Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
            Route::put('/{notification}', [NotificationController::class, 'updateReadStatus']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });
    });
});