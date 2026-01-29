<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSettlements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-settlements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process settlements for payment requests that are due (after 3 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting settlement processing...');

        // Find all payment requests that are eligible for settlement
        $settlements = PaymentRequest::where('settlement_status', 'pending_settlement')
            ->where('status', 'success')
            ->whereNotNull('settlement_due_date')
            ->where('settlement_due_date', '<=', now())
            ->get();

        $processedCount = 0;
        $failedCount = 0;

        foreach ($settlements as $paymentRequest) {
            try {
                // Find the merchant associated with this payment request
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
                    // Use database transaction to ensure atomicity for each settlement
                    DB::transaction(function () use (&$paymentRequest, $merchantId) {
                        $merchant = Merchant::findOrFail($merchantId);

                        // Add the payment amount to the merchant's balance
                        $merchant->increment('saldo', $paymentRequest->price);

                        // Update the payment request to mark it as settled
                        $paymentRequest->update([
                            'settlement_status' => 'settled',
                            'settled_at' => now()
                        ]);

                        Log::info('Settlement processed successfully', [
                            'payment_request_id' => $paymentRequest->id,
                            'merchant_id' => $merchant->id,
                            'amount' => $paymentRequest->price,
                            'new_merchant_balance' => $merchant->saldo
                        ]);
                    }, 3); // Retry up to 3 times in case of deadlock

                    $this->info("Processed settlement for payment request #{$paymentRequest->id}");
                    $processedCount++;
                } else {
                    Log::warning('Could not find merchant for payment request during settlement', [
                        'payment_request_id' => $paymentRequest->id
                    ]);

                    // Use transaction for consistency even when merchant is not found
                    DB::transaction(function () use (&$paymentRequest) {
                        // Mark the settlement as failed/cancelled if merchant cannot be found
                        $paymentRequest->update([
                            'settlement_status' => 'cancelled',
                            'settled_at' => now()
                        ]);
                    });

                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error('Error processing settlement: ' . $e->getMessage(), [
                    'payment_request_id' => $paymentRequest->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $failedCount++;
            }
        }

        $this->info("Settlement processing completed. Processed: {$processedCount}, Failed: {$failedCount}");
    }
}
