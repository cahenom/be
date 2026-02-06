<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $title;
    protected $body;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $title, $body, array $data = [])
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\FirebaseService $firebaseService): void
    {
        $user = \App\Models\User::find($this->userId);
        if (!$user) {
            \Log::warning("FCM Job: User not found for ID {$this->userId}");
            return;
        }

        try {
            $firebaseService->sendNotificationToUser($user, $this->title, $this->body, $this->data);
            \Log::info("FCM Job: Notification sent to user {$this->userId}");
        } catch (\Exception $e) {
            \Log::error("FCM Job: Failed to send notification to user {$this->userId}: " . $e->getMessage());
        }
    }
}
