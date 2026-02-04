<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Merchant;
use App\Models\TransactionModel;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ExpirePendingPaymentRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-pending-payment-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire pending payment requests that have been pending for more than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pending payment request expiration process...');

        // Find all pending payment requests that are older than 1 day (24 hours)
        $expiredRequests = PaymentRequest::where('status', 'pending')
            ->where('created_at', '<', now()->subDay())
            ->get();

        $expiredCount = 0;
        $failedCount = 0;

        foreach ($expiredRequests as $paymentRequest) {
            try {
                // Update the payment request status to 'cancelled' due to expiration
                $paymentRequest->update([
                    'status' => 'cancelled',
                    'expires_at' => now() // Mark the time it was expired
                ]);

                // Find the merchant associated with this payment request to send webhook notification
                $merchantId = null;
                if (isset($paymentRequest->metadata['merchant_id'])) {
                    $merchantId = $paymentRequest->metadata['merchant_id'];
                } else {
                    // If merchant_id is not in metadata, try to find it from the merchant controller's logic
                    $merchant = Merchant::where('email', $paymentRequest->email)->first();
                    if ($merchant) {
                        $merchantId = $merchant->id;
                    }
                }

                if ($merchantId) {
                    $merchant = Merchant::findOrFail($merchantId);

                    // Send webhook notification to merchant about cancelled payment
                    $this->sendWebhookNotification($merchant, $paymentRequest, 'cancelled');
                } else {
                    Log::warning('Could not find merchant for expired payment request', [
                        'payment_request_id' => $paymentRequest->id
                    ]);
                }

                // Attempt to create transaction record for the cancellation
                try {
                    TransactionModel::insert_merchant_payment_transaction(
                        $paymentRequest->external_id,
                        $paymentRequest->name,
                        $paymentRequest->email,
                        $paymentRequest->price,
                        $paymentRequest->destination,
                        'cancelled',
                        $paymentRequest->user_id
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to create transaction for cancelled payment: ' . $e->getMessage(), [
                        'payment_request_id' => $paymentRequest->id,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $this->info("Expired payment request #{$paymentRequest->id}");
                $expiredCount++;
            } catch (\Exception $e) {
                Log::error('Error expiring payment request: ' . $e->getMessage(), [
                    'payment_request_id' => $paymentRequest->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $failedCount++;
            }
        }

        $this->info("Expiration process completed. Expired: {$expiredCount}, Failed: {$failedCount}");
    }

    /**
     * Send webhook notification to merchant
     */
    private function sendWebhookNotification($merchant, $paymentRequest, $status)
    {
        if (empty($merchant->webhook)) {
            Log::info('No webhook URL found for merchant', ['merchant_id' => $merchant->id]);
            return;
        }

        $payload = [
            'payment_id' => $paymentRequest->external_id,
            'status' => $status,
            'amount' => $paymentRequest->price,
            'user_email' => $paymentRequest->email,
            'destination' => $paymentRequest->destination,
            'product_name' => $paymentRequest->name,
            'timestamp' => now()->toISOString(),
            'payment_request_id' => $paymentRequest->id,
            'reason' => 'Expired after 24 hours without user action'
        ];

        Log::info('Sending webhook notification to merchant for expired payment', [
            'merchant_id' => $merchant->id,
            'webhook_url' => $merchant->webhook,
            'payload' => $payload
        ]);

        try {
            $response = \Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'PaymentSystem/1.0'
                ])
                ->post($merchant->webhook, $payload);

            if ($response->successful()) {
                Log::info('Webhook notification sent successfully for expired payment', [
                    'merchant_id' => $merchant->id,
                    'status_code' => $response->status()
                ]);
            } else {
                Log::warning('Webhook notification failed for expired payment', [
                    'merchant_id' => $merchant->id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending webhook notification for expired payment: ' . $e->getMessage(), [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
