<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseService;

class FirebaseController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Kirim notifikasi ke semua user (admin only)
     */
    public function sendToAll(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string'
        ]);

        $successCount = 0;
        $failureCount = 0;
        $totalUsers = 0;

        // Ambil semua user dengan token secara bertahap (chunking)
        User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->chunk(500, function ($users) use ($request, &$successCount, &$failureCount, &$totalUsers) {
                $result = $this->firebaseService->sendNotificationToUsers(
                    $users->all(),
                    $request->title,
                    $request->body
                );

                $successCount += $result['success_count'] ?? 0;
                $failureCount += $result['failure_count'] ?? 0;
                $totalUsers += count($users);
            });

        if ($totalUsers === 0) {
            return response()->json(['error' => 'No users with FCM tokens'], 400);
        }

        return response()->json([
            'message' => 'Notification processed for ' . $totalUsers . ' users',
            'data' => [
                'success' => $successCount > 0,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total' => $totalUsers
            ]
        ]);
    }


    /**
     * Kirim notifikasi ke beberapa user (admin only)
     */
    public function sendToMultiple(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        // Ambil user berdasarkan IDs
        $users = User::whereIn('id', $request->user_ids)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            return response()->json(['error' => 'No valid users with FCM tokens'], 400);
        }

        $result = $this->firebaseService->sendNotificationToUsers(
            $users->all(),
            $request->title,
            $request->body
        );

        if ($result['success']) {
            return response()->json([
                'message' => 'Notification sent to ' . ($result['success_count'] ?? 0) . ' users',
                'data' => $result
            ]);
        }

        return response()->json([
            'error' => 'Failed to send notification',
            'details' => $result['error'] ?? 'Unknown error'
        ], 500);
    }

    /**
     * Versi alternatif: Kirim langsung dengan token (lebih sederhana)
     */
    public function sendBulkByTokens(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        // Ambil token langsung
        $tokens = User::when($request->user_ids, function($query) use ($request) {
                return $query->whereIn('id', $request->user_ids);
            })
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json(['error' => 'No valid FCM tokens found'], 400);
        }

        // Kirim langsung dengan tokens (tanpa user objects)
        $result = $this->firebaseService->sendNotification(
            $tokens,
            $request->title,
            $request->body
        );

        if ($result['success']) {
            return response()->json([
                'message' => 'Notification sent to ' . ($result['success_count'] ?? 0) . ' users',
                'data' => $result
            ]);
        }

        return response()->json([
            'error' => 'Failed to send notification',
            'details' => $result['error'] ?? 'Unknown error'
        ], 500);
    }

    /**
     * Test notification to current user
     */
    public function sendTestNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string'
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $result = $this->firebaseService->sendNotificationToUser(
            $user,
            $request->title,
            $request->body
        );

        if ($result['success']) {
            return response()->json([
                'message' => 'Test notification sent',
                'data' => $result
            ]);
        }

        return response()->json([
            'error' => 'Failed to send notification',
            'details' => $result['error'] ?? 'Unknown error'
        ], 500);
    }

    /**
     * Simpan token FCM
     */
    public function saveToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'message' => 'Token saved successfully'
        ]);
    }

    /**
     * Hapus token FCM
     */
    public function removeToken(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->fcm_token = null;
        $user->save();

        return response()->json([
            'message' => 'Token removed successfully'
        ]);
    }
}