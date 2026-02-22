<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Services\FirebaseService;

class XenditWebhookController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle Xendit invoice webhook
     */
    public function handle(Request $request)
    {
        // Log the incoming webhook request
        Log::info('Xendit webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'timestamp' => now()->toISOString(),
        ]);

        // Xendit uses x-callback-token header for signature verification
        $xCallbackToken = $request->header('x-callback-token');
        $xenditCallbackToken = env('XENDIT_CALLBACK_TOKEN'); // Using a different env variable for callback token

        // Verify webhook signature using x-callback-token (implement according to Xendit docs)
        if (!empty($xenditCallbackToken) && !hash_equals($xenditCallbackToken, $xCallbackToken)) {
            Log::warning('Xendit webhook signature verification failed', [
                'provided_token' => $xCallbackToken,
                'expected_token' => $xenditCallbackToken,
                'timestamp' => now()->toISOString(),
            ]);
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        // For Xendit invoice webhooks, the payload is the invoice data itself
        // Not wrapped in an 'event' and 'data' structure like some other services
        $invoiceData = $request->all();
        $externalId = $invoiceData['external_id'] ?? null;
        $status = $invoiceData['status'] ?? null;

        Log::info('Xendit invoice processed', [
            'external_id' => $externalId,
            'invoice_id' => $invoiceData['id'] ?? null,
            'status' => $status,
            'amount' => $invoiceData['amount'] ?? null,
            'payment_method' => $invoiceData['payment_method'] ?? null,
            'timestamp' => now()->toISOString(),
        ]);

        if ($externalId) {
            // Find the deposit record by external_id
            \DB::transaction(function () use ($externalId, $invoiceData, $status) {
                // Lock the deposit record to prevent concurrent updates
                $deposit = \App\Models\Deposit::where('external_id', $externalId)->lockForUpdate()->first();

                if (!$deposit) {
                    Log::warning("Deposit record not found for external_id: {$externalId}", [
                        'external_id' => $externalId,
                        'invoice_data' => $invoiceData,
                    ]);
                    return;
                }

                // If already paid, skip to prevent double-deposit (but we might still want to update other metadata if status changed)
                if ($deposit->status === 'paid' && $status !== 'SETTLED') {
                    Log::info("Deposit {$externalId} already paid. Skipping balance update.");
                    return;
                }

                // Mapping Xendit status to our internal status if needed
                // Xendit statuses: PENDING, PAID, SETTLED, EXPIRED
                $internalStatus = strtolower($status);

                // Update deposit status and response
                $deposit->update([
                    'status' => $internalStatus,
                    'xendit_response' => array_merge($deposit->xendit_response ?? [], $invoiceData)
                ]);

                $user = \App\Models\User::where('id', $deposit->user_id)->lockForUpdate()->first();

                if ($user) {
                    // Update user balance ONLY if status is PAID and it wasn't already paid
                    if ($status === 'PAID' && $deposit->getOriginal('status') !== 'paid') {
                        $amount = $invoiceData['amount'];
                        $user->saldo += $amount;
                        $user->save();

                        Log::info("User balance updated via Xendit for user ID {$user->id}. Amount: {$amount}", [
                            'user_id' => $user->id,
                            'new_balance' => $user->saldo,
                            'external_id' => $externalId,
                        ]);
                    }

                    // Send FCM Notification
                    $this->sendFcmNotification($user, $status, $invoiceData['amount'], $externalId);
                }
            });
        }

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Send FCM notification to user about deposit status
     */
    private function sendFcmNotification($user, $status, $amount, $externalId)
    {
        try {
            if ($user && $user->getFcmToken()) {
                $statusLabel = $status;
                if ($status === 'PAID') $statusLabel = 'BERHASIL';
                if ($status === 'EXPIRED') $statusLabel = 'KADALUARSA';
                if ($status === 'PENDING') $statusLabel = 'MENUNGGU PEMBAYARAN';

                $title = 'Status Deposit';
                $body = "Deposit sebesar Rp " . number_format($amount, 0, ',', '.') . " statusnya: {$statusLabel}";

                if ($status === 'PAID') {
                    $title = 'Deposit Berhasil!';
                    $body = "Saldo sebesar Rp " . number_format($amount, 0, ',', '.') . " telah ditambahkan ke akun Anda.";
                }

                $this->firebaseService->sendNotificationToUser(
                    $user,
                    $title,
                    $body,
                    [
                        'type' => 'deposit_update',
                        'external_id' => $externalId,
                        'status' => $status,
                        'amount' => $amount,
                    ]
                );

                Log::info("FCM Deposit notification sent to user {$user->id}", ['status' => $status]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send FCM deposit notification: " . $e->getMessage());
        }
    }

    /**
     * Calculate signature for webhook verification
     * Note: This method is not currently used as Xendit uses x-callback-token header for verification
     */
    private function calculateSignature($payload, $secret)
    {
        if (!$secret) {
            return '';
        }

        return hash_hmac('sha256', $payload, $secret);
    }
}