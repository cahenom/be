<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
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

        if ($status === 'PAID' && $externalId) {
            // Find the deposit record by external_id
            \DB::transaction(function () use ($externalId, $invoiceData) {
                // Lock the deposit record to prevent concurrent updates
                $deposit = \App\Models\Deposit::where('external_id', $externalId)->lockForUpdate()->first();

                if (!$deposit) {
                    Log::warning("Deposit record not found for external_id: {$externalId}", [
                        'external_id' => $externalId,
                        'invoice_data' => $invoiceData,
                    ]);
                    return;
                }

                // 🛡️ IDEMPOTENCY: If already paid, skip to prevent double-deposit
                if ($deposit->status === 'paid') {
                    Log::info("Deposit {$externalId} already processed. Skipping balance update.");
                    return;
                }

                // Update deposit status
                $deposit->update([
                    'status' => 'paid',
                    'xendit_response' => array_merge($deposit->xendit_response ?? [], $invoiceData)
                ]);

                $user = \App\Models\User::where('id', $deposit->user_id)->lockForUpdate()->first();

                if ($user) {
                    // Update user balance with the paid amount
                    $amount = $invoiceData['amount'];
                    $user->saldo += $amount;
                    $user->save();

                    Log::info("User balance updated via Xendit for user ID {$user->id}. Amount: {$amount}", [
                        'user_id' => $user->id,
                        'new_balance' => $user->saldo,
                        'external_id' => $externalId,
                        'invoice_id' => $invoiceData['id'],
                    ]);
                }
            });
        }

        return response()->json(['status' => 'received'], 200);
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