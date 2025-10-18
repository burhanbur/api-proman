<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\UserNotificationPreference;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Traits\ApiResponse;

use Exception;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index() 
    {
        $user = auth()->user();

        try {
            $data = Notification::with(['user'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return $this->successResponse(
                NotificationResource::collection($data),
                'Notifications retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:info,success,warning,error',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'detail_url' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            $data = Notification::create([
                'uuid' => Str::uuid(),
                'user_id' => auth()->user()->id,
                'type' => $request->type, // info, success, warning, error
                'title' => $request->title,
                'message' => $request->message,
                'detail_url' => $request->detail_url
            ]);

            DB::commit();

            return $this->successResponse(
                NotificationResource::collection($data),
                'Berhasil menyimpan notifikasi.'
            );
        } catch (Exception $ex){
            DB::rollBack();
            Log::error('Error storing notification: ' . $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    public function updateReadStatus(Request $request, $uuid)
    {
        try {
            $notification = Notification::with(['user'])->where('uuid', $uuid)->first();
            if (!$notification) {
                return $this->errorResponse('Data notifikasi tidak ditemukan.', 404);
            }

            $notification->update([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);

            return $this->successResponse(
                new NotificationResource($notification),
                'Notifikasi telah ditandai sebagai dibaca.'
            );
        } catch (Exception $ex) {
            Log::error('Error updating notification: ' . $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $notification = Notification::where('uuid', $uuid)->first();
            if (!$notification) {
                return $this->errorResponse('Data notifikasi tidak ditemukan.', 404);
            }

            $notification->delete();

            return $this->successResponse(null, 'Berhasil menghapus notifikasi.');
        } catch (Exception $ex) {
            Log::error('Error deleting notification: ' . $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    public function markAllAsRead()
    {
        try {
            $userId = auth()->user()->id;
            Notification::where(['user_id' => $userId, 'is_read' => false])->update(['is_read' => true, 'read_at' => now()]);

            return $this->successResponse(null, 'Semua notifikasi telah ditandai sebagai dibaca.');
        } catch (Exception $ex) {
            Log::error('Error marking all notifications as read: ' . $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function unreadCount()
    {
        try {
            $notificationService = app(NotificationService::class);
            $count = $notificationService->getUnreadCount(auth()->id());

            return $this->successResponse(['count' => $count], 'Unread count retrieved successfully');
        } catch (Exception $ex) {
            Log::error('Error getting unread count: ' . $ex->getMessage());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    /**
     * Get user notification preferences
     */
    public function getPreferences()
    {
        try {
            $notificationService = app(NotificationService::class);
            $preferences = $notificationService->getUserPreferences(auth()->id());

            return $this->successResponse($preferences, 'Preferences retrieved successfully');
        } catch (Exception $ex) {
            Log::error('Error getting preferences: ' . $ex->getMessage());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    /**
     * Update user notification preference
     */
    public function updatePreference(Request $request, $eventId)
    {
        $validator = Validator::make($request->all(), [
            'is_enabled' => 'required|boolean',
            'channel_email' => 'nullable|boolean',
            'channel_push' => 'nullable|boolean',
            'channel_in_app' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $notificationService = app(NotificationService::class);
            
            $preference = $notificationService->setUserPreference(
                auth()->id(),
                $eventId,
                $request->only(['is_enabled', 'channel_email', 'channel_push', 'channel_in_app'])
            );

            return $this->successResponse($preference, 'Preference updated successfully');
        } catch (Exception $ex) {
            Log::error('Error updating preference: ' . $ex->getMessage());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    /**
     * Get all available notification events
     */
    public function getEvents()
    {
        try {
            $events = NotificationEvent::active()
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            return $this->successResponse($events, 'Events retrieved successfully');
        } catch (Exception $ex) {
            Log::error('Error getting events: ' . $ex->getMessage());
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }
}
