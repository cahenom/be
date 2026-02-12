<?php

namespace App\Services;

use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use App\Models\User;

class FirebaseService
{
    protected ?Messaging $messaging = null;

    public function __construct(?Messaging $messaging = null)
    {
        if ($messaging) {
            $this->messaging = $messaging;
        } else {
            $this->messaging = app('firebase.messaging');
        }
    }

    /**
     * Send a notification to a specific user
     */
    public function sendNotificationToUser(User $user, string $title, string $body, array $data = []): array
    {
        $fcmToken = $user->fcm_token ?? $user->getFcmToken();

        if (!$fcmToken) {
            throw new \Exception("User does not have an FCM token");
        }

        return $this->sendNotification([$fcmToken], $title, $body, $data);
    }

    /**
     * Send a notification to multiple users
     */
    public function sendNotificationToUsers(array $users, string $title, string $body, array $data = []): array
    {
        $tokens = [];

        foreach ($users as $user) {
            $fcmToken = $user->fcm_token ?? $user->getFcmToken();
            if ($fcmToken) {
                $tokens[] = $fcmToken;
            }
        }

        if (empty($tokens)) {
            throw new \Exception("No users have FCM tokens");
        }

        return $this->sendNotification($tokens, $title, $body, $data);
    }

    /**
     * Send a notification to specific device tokens
     */
    public function sendNotification(array $tokens, string $title, string $body, array $data = []): array
    {
        // Pastikan tokens adalah array
        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }

        // Filter token yang valid
        $tokens = array_filter($tokens, function($token) {
            return !empty($token) && is_string($token) && trim($token) !== '';
        });

        if (empty($tokens)) {
            return [
                'success' => false,
                'error' => 'No valid tokens provided'
            ];
        }

        // Convert data values to strings
        $processedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $processedData[$key] = json_encode($value);
            } else {
                $processedData[$key] = (string) $value;
            }
        }

        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
            ->withData($processedData);

        try {
            $response = $this->messaging->sendMulticast($message, $tokens);
            
            // Hitung hasil yang berhasil dan gagal
            $successCount = $response->successes()->count();
            $failureCount = $response->failures()->count();
            
            return [
                'success' => $successCount > 0,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total' => count($tokens),
                'message' => $successCount > 0 ? 'Notifications sent successfully' : 'All notifications failed'
            ];
            
        } catch (MessagingException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'success_count' => 0,
                'failure_count' => count($tokens),
                'total' => count($tokens)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'success_count' => 0,
                'failure_count' => count($tokens),
                'total' => count($tokens)
            ];
        }
    }

    /**
     * Subscribe user to a topic
     */
    public function subscribeToTopic(User $user, string $topic): array
    {
        $fcmToken = $user->fcm_token ?? $user->getFcmToken();

        if (!$fcmToken) {
            throw new \Exception("User does not have an FCM token");
        }

        try {
            $this->messaging->subscribeToTopic($topic, [$fcmToken]);
            return [
                'success' => true,
                'message' => "Successfully subscribed to topic: {$topic}",
            ];
        } catch (MessagingException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unsubscribe user from a topic
     */
    public function unsubscribeFromTopic(User $user, string $topic): array
    {
        $fcmToken = $user->fcm_token ?? $user->getFcmToken();

        if (!$fcmToken) {
            throw new \Exception("User does not have an FCM token");
        }

        try {
            $this->messaging->unsubscribeFromTopic($topic, [$fcmToken]);
            return [
                'success' => true,
                'message' => "Successfully unsubscribed from topic: {$topic}",
            ];
        } catch (MessagingException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}