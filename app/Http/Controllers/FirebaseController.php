<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class FirebaseController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Save FCM token for authenticated user
     */
    public function saveToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $user->updateFcmToken($request->fcm_token);

        return response()->json([
            'message' => 'FCM token saved successfully',
            'fcm_token' => $request->fcm_token
        ]);
    }

    /**
     * Send test notification to authenticated user
     */
    public function sendTestNotification(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $fcmToken = $user->getFcmToken();

        if (!$fcmToken) {
            return response()->json(['error' => 'User does not have an FCM token'], 400);
        }

        $result = $this->firebaseService->sendNotificationToUser(
            $user,
            'Test Notification',
            'This is a test notification from your Laravel app!',
            ['type' => 'test_notification']
        );

        if ($result['success']) {
            return response()->json([
                'message' => 'Notification sent successfully',
                'result' => $result
            ]);
        } else {
            Log::error('FCM Error: ' . $result['error']);
            return response()->json([
                'error' => 'Failed to send notification',
                'details' => $result['error']
            ], 500);
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulkNotification(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();

        if ($users->isEmpty()) {
            return response()->json(['error' => 'No valid users found'], 400);
        }

        $result = $this->firebaseService->sendNotificationToUsers(
            $users,
            $request->title,
            $request->body,
            ['type' => 'bulk_notification']
        );

        if ($result['success']) {
            return response()->json([
                'message' => 'Notifications sent successfully',
                'result' => $result
            ]);
        } else {
            Log::error('FCM Bulk Error: ' . $result['error']);
            return response()->json([
                'error' => 'Failed to send notifications',
                'details' => $result['error']
            ], 500);
        }
    }
}
