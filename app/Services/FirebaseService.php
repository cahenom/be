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
            // Get from service container if not injected
            $this->messaging = app('firebase.messaging');
        }
    }

    /**
     * Send a notification to a specific user
     */
    public function sendNotificationToUser(User $user, string $title, string $body, array $data = []): array
    {
        $fcmToken = $user->getFcmToken();

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
            $fcmToken = $user->getFcmToken();
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
        // Convert all values to strings as FCM requires string values
        $processedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $processedData[$key] = json_encode($value);
            } elseif (is_object($value)) {
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
            // Just return success without parsing response details
            return [
                'success' => true,
                'message' => 'Notifications sent successfully',
            ];
        } catch (MessagingException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Subscribe user to a topic
     */
    public function subscribeToTopic(User $user, string $topic): array
    {
        $fcmToken = $user->getFcmToken();

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
        $fcmToken = $user->getFcmToken();

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