<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\PaymentRequestCollection;
use App\Http\Resources\PaymentRequestResource;
use App\Models\Merchant;
use App\Models\PaymentRequest;
use App\Models\TransactionModel;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentRequestController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    /**
     * Get pending payment requests for the authenticated user
     */
    public function getUserPendingRequests(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'User not authenticated',
                'data' => null
            ], 401);
        }

        $paymentRequests = PaymentRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('user') // Eager load relasi user untuk menghindari N+1 query
            ->orderBy('created_at', 'desc')
            ->get();

        return new PaymentRequestCollection($paymentRequests);
    }

    /**
     * Approve a payment request
     */
    public function approvePaymentRequest(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'User not authenticated',
                'data' => null
            ], 401);
        }

        // Coba cari berdasarkan ID internal dulu, jika tidak ditemukan coba external_id
        $paymentRequest = PaymentRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->with('user') // Eager load relasi user untuk menghindari N+1 query
            ->first();

        // Jika tidak ditemukan berdasarkan ID internal, coba cari berdasarkan external_id
        if (!$paymentRequest) {
            $paymentRequest = PaymentRequest::where('external_id', $id)
                ->where('user_id', $user->id)
                ->with('user') // Eager load relasi user untuk menghindari N+1 query
                ->first();
        }

        if (!$paymentRequest) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Payment request not found',
                'data' => null
            ], 404);
        }

        if ($paymentRequest->status !== 'pending') {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Payment request is not in pending status',
                'data' => null
            ], 400);
        }

        // Check if user has sufficient balance
        $price = floatval($paymentRequest->price);
        $userBalance = floatval($user->saldo ?? 0);

        if ($userBalance < $price) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Maaf, saldo Anda tidak cukup untuk menyelesaikan pembayaran ini',
                'data' => [
                    'error' => 'Saldo tidak cukup',
                    'current_balance' => $userBalance,
                    'required_amount' => $price,
                    'indonesian_message' => 'Saldo kamu kurang nih!'
                ],
            ], 400);
        }

        // Update status to approved first
        $paymentRequest->update(['status' => 'approved']);

        // Calculate deduction amount
        $deductionAmount = $paymentRequest->price;

        // Calculate previous balance before update
        $previousBalance = $user->saldo;

        \Log::info('About to update user and merchant balances', [
            'user_id' => $user->id,
            'current_user_balance_before_update' => $user->saldo,
            'deduction_amount' => $deductionAmount
        ]);

        // Use database transaction to ensure atomicity of balance deduction (but not merchant balance addition yet)
        \DB::transaction(function () use ($user, $paymentRequest, $deductionAmount) {
            // Reload user to ensure we have a fresh model instance
            $freshUser = \App\Models\User::findOrFail($user->id);
            $freshUser->saldo -= $deductionAmount;
            $freshUser->save();

            // Find the merchant associated with this payment request
            $merchantId = null;
            if (isset($paymentRequest->metadata['merchant_id'])) {
                $merchantId = $paymentRequest->metadata['merchant_id'];
            } else {
                // If merchant_id is not in metadata, try to find it from the merchant controller's logic
                // This assumes the merchant_id might be stored elsewhere in the payment request
                $merchant = \App\Models\Merchant::where('email', $paymentRequest->email)->first();
                if ($merchant) {
                    $merchantId = $merchant->id;
                }
            }

            if ($merchantId) {
                $freshMerchant = \App\Models\Merchant::findOrFail($merchantId);

                // Update payment request to indicate it's pending settlement
                $paymentRequest->update([
                    'settlement_status' => 'pending_settlement',
                    'settlement_due_date' => now()->addDays(3), // 3 days settlement period
                    'status' => 'success' // Change status to success after approval
                ]);

                \Log::info('Payment request marked for settlement', [
                    'payment_request_id' => $paymentRequest->id,
                    'merchant_id' => $merchantId,
                    'amount' => $deductionAmount,
                    'settlement_due_date' => $paymentRequest->settlement_due_date
                ]);

                // Send webhook notification to merchant about successful payment (pending settlement)
                $this->sendWebhookNotification($freshMerchant, $paymentRequest, 'completed_pending_settlement', $deductionAmount);
            } else {
                \Log::warning('Could not find merchant for payment request', [
                    'payment_request_id' => $paymentRequest->id
                ]);

                // Still update the settlement fields even if merchant isn't found
                $paymentRequest->update([
                    'settlement_status' => 'pending_settlement',
                    'settlement_due_date' => now()->addDays(3), // 3 days settlement period
                    'status' => 'success' // Change status to success after approval
                ]);
            }
        });

        \Log::info('User balance update result', [
            'user_id' => $user->id,
            'previous_balance' => $previousBalance,
            'deduction_amount' => $deductionAmount,
            'current_balance_after_update' => $user->fresh()->saldo  // Refresh user data from DB
        ]);

        // Attempt to create transaction, but don't fail the whole operation if it fails
        $transaction = null;
        try {
            $transaction = TransactionModel::insert_merchant_payment_transaction(
                $paymentRequest->external_id,
                $paymentRequest->name,
                $user->email,
                $paymentRequest->price,
                $paymentRequest->destination,
                'completed',
                $user->id
            );
        } catch (\Exception $e) {
            // Log the error but don't fail the payment approval
            \Log::error('Failed to create transaction for approved payment: ' . $e->getMessage(), [
                'payment_request_id' => $paymentRequest->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Calculate new balance for response
        $newBalance = floatval($user->fresh()->saldo);

        // Send FCM notification to the user about the successful payment
        $notificationData = [
            'type' => 'payment_success',
            'payment_id' => $paymentRequest->external_id,
            'product_name' => $paymentRequest->name,
            'destination' => $paymentRequest->destination,
            'price' => $paymentRequest->price,
            'status' => 'completed',
            'new_balance' => $newBalance,
        ];

        try {
            $result = $this->firebaseService->sendNotificationToUser(
                $user,
                'Pembayaran Berhasil',
                "Pembayaran untuk {$paymentRequest->name} berhasil diproses.",
                $notificationData
            );

            if ($result['success']) {
                \Log::info('FCM notification sent successfully to user', ['result' => $result]);
            } else {
                \Log::warning('Failed to send FCM notification to user: ' . $result['error']);
            }
        } catch (\Exception $notificationException) {
            \Log::error('Exception occurred while sending FCM notification to user: ' . $notificationException->getMessage(), [
                'exception' => $notificationException->getMessage(),
                'trace' => $notificationException->getTraceAsString()
            ]);
        }

        // Here you could add business logic to process the actual payment
        // For now, we'll just return success

        return new ApiResponseResource([
            'status' => true,
            'message' => 'Payment request approved successfully',
            'data' => [
                'payment_request' => $paymentRequest,
                'transaction' => $transaction,  // Will be null if creation failed
                'new_balance' => $newBalance
            ],
        ]);
    }

    /**
     * Reject a payment request
     */
    public function rejectPaymentRequest(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'User not authenticated',
                'data' => null
            ], 401);
        }

        // Coba cari berdasarkan ID internal dulu, jika tidak ditemukan coba external_id
        $paymentRequest = PaymentRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->with('user') // Eager load relasi user untuk menghindari N+1 query
            ->first();

        // Jika tidak ditemukan berdasarkan ID internal, coba cari berdasarkan external_id
        if (!$paymentRequest) {
            $paymentRequest = PaymentRequest::where('external_id', $id)
                ->where('user_id', $user->id)
                ->with('user') // Eager load relasi user untuk menghindari N+1 query
                ->first();
        }

        if (!$paymentRequest) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Payment request not found',
                'data' => null
            ], 404);
        }

        if ($paymentRequest->status !== 'pending') {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Payment request is not in pending status',
                'data' => null
            ], 400);
        }

        // Update status to rejected
        $paymentRequest->update(['status' => 'rejected']);

        // Find the merchant associated with this payment request to send webhook notification
        $merchantId = null;
        if (isset($paymentRequest->metadata['merchant_id'])) {
            $merchantId = $paymentRequest->metadata['merchant_id'];
        } else {
            // If merchant_id is not in metadata, try to find it from the merchant controller's logic
            $merchant = \App\Models\Merchant::where('email', $paymentRequest->email)->first();
            if ($merchant) {
                $merchantId = $merchant->id;
            }
        }

        if ($merchantId) {
            $merchant = \App\Models\Merchant::findOrFail($merchantId);

            // Send webhook notification to merchant about rejected payment
            $this->sendWebhookNotification($merchant, $paymentRequest, 'rejected');
        } else {
            \Log::warning('Could not find merchant for payment request', [
                'payment_request_id' => $paymentRequest->id
            ]);
        }

        // Attempt to create transaction, but don't fail the whole operation if it fails
        $transaction = null;
        try {
            $transaction = TransactionModel::insert_merchant_payment_transaction(
                $paymentRequest->external_id,
                $paymentRequest->name,
                $user->email,
                $paymentRequest->price,
                $paymentRequest->destination,
                'rejected',
                $user->id
            );
        } catch (\Exception $e) {
            // Log the error but don't fail the payment rejection
            \Log::error('Failed to create transaction for rejected payment: ' . $e->getMessage(), [
                'payment_request_id' => $paymentRequest->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Send FCM notification to the user about the rejected payment
        $notificationData = [
            'type' => 'payment_rejected',
            'payment_id' => $paymentRequest->external_id,
            'product_name' => $paymentRequest->name,
            'destination' => $paymentRequest->destination,
            'price' => $paymentRequest->price,
            'status' => 'rejected',
        ];

        try {
            $result = $this->firebaseService->sendNotificationToUser(
                $user,
                'Pembayaran Ditolak',
                "Pembayaran untuk {$paymentRequest->name} ditolak.",
                $notificationData
            );

            if ($result['success']) {
                \Log::info('FCM notification sent successfully to user for rejected payment', ['result' => $result]);
            } else {
                \Log::warning('Failed to send FCM notification to user for rejected payment: ' . $result['error']);
            }
        } catch (\Exception $notificationException) {
            \Log::error('Exception occurred while sending FCM notification to user for rejected payment: ' . $notificationException->getMessage(), [
                'exception' => $notificationException->getMessage(),
                'trace' => $notificationException->getTraceAsString()
            ]);
        }

        return new ApiResponseResource([
            'status' => true,
            'message' => 'Payment request rejected successfully',
            'data' => [
                'payment_request' => $paymentRequest,
                'transaction' => $transaction  // Will be null if creation failed
            ],
        ]);
    }

    /**
     * Get a specific payment request
     */
    public function showPaymentRequest(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'User not authenticated',
                'data' => null
            ], 401);
        }

        $paymentRequest = PaymentRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->with('user') // Eager load relasi user untuk menghindari N+1 query
            ->first();

        if (!$paymentRequest) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Payment request not found',
                'data' => null
            ], 404);
        }

        return new PaymentRequestResource($paymentRequest);
    }

    /**
     * Send webhook notification to merchant
     */
    private function sendWebhookNotification($merchant, $paymentRequest, $status, $amount = null)
    {
        if (empty($merchant->webhook)) {
            \Log::info('No webhook URL found for merchant', ['merchant_id' => $merchant->id]);
            return;
        }

        $payload = [
            'payment_id' => $paymentRequest->external_id,
            'status' => $status,
            'amount' => $amount,
            'user_email' => $paymentRequest->email,
            'destination' => $paymentRequest->destination,
            'product_name' => $paymentRequest->name,
            'timestamp' => now()->toISOString(),
            'payment_request_id' => $paymentRequest->id,
        ];

        \Log::info('Sending webhook notification to merchant', [
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
                \Log::info('Webhook notification sent successfully', [
                    'merchant_id' => $merchant->id,
                    'status_code' => $response->status()
                ]);
            } else {
                \Log::warning('Webhook notification failed', [
                    'merchant_id' => $merchant->id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending webhook notification: ' . $e->getMessage(), [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}