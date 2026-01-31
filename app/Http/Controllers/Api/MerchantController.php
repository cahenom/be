<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\Merchant;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle merchant payment request
     * This endpoint receives payment details from merchants and sends notifications to users
     */

    /**
 * @scramble-skip
 */
    public function handlePaymentRequest(Request $request)
    {
        // Validate API key from header
        $apiKey = $request->header('X-API-Key') ?: $request->header('API-Key') ?: $request->input('api_key');

        if (!$apiKey) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'API key is required',
                'data' => null
            ], 401);
        }

        // Find merchant by API key
        $merchant = Merchant::where('api_key', $apiKey)->first();

        if (!$merchant) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Unauthorized: Invalid API key',
                'data' => null
            ], 401);
        }

        \Log::info('Merchant payment request received', [
            'merchant_id' => $merchant->id,
            'request_data' => $request->all()
        ]);

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'id' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for merchant payment request', $validator->errors()->toArray());
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors()
            ], 400);
        }

        try {
            // Find user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                \Log::warning('User not found for merchant payment request', ['email' => $request->email]);
                return new ApiResponseResource([
                    'status' => false,
                    'message' => 'User not found with the provided email',
                    'data' => null
                ], 404);
            }

            // Check if a payment request with this external_id already exists
            $existingRequest = PaymentRequest::where('external_id', $request->id)->first();
            if ($existingRequest) {
                \Log::warning('Duplicate payment request ID detected', [
                    'external_id' => $request->id,
                    'existing_request_id' => $existingRequest->id
                ]);

                return new ApiResponseResource([
                    'status' => false,
                    'message' => 'A payment request with this ID already exists',
                    'data' => null,
                ], 409); // 409 Conflict status code
            }

            \Log::info('Creating payment request record', [
                'external_id' => $request->id,
                'user_id' => $user->id,
                'merchant_id' => $merchant->id
            ]);

            // Create payment request record
            $paymentRequest = PaymentRequest::create([
                'external_id' => $request->id,
                'name' => $request->name,
                'destination' => $request->destination,
                'price' => $request->price,
                'email' => $request->email,
                'user_id' => $user->id,
                'status' => 'pending',
                'metadata' => [
                    'original_request' => $request->all(),
                    'merchant_id' => $merchant->id
                ]
            ]);

            \Log::info('Payment request created successfully', ['id' => $paymentRequest->id]);

            // Prepare notification data
            $notificationData = [
                'type' => 'payment_request',
                'payment_id' => $request->id,
                'name' => $request->name,
                'destination' => $request->destination,
                'price' => $request->price,
                'email' => $request->email,
                'payment_request_id' => $paymentRequest->id,
                'merchant_id' => $merchant->id,
            ];

            \Log::info('Sending FCM notification to user', [
                'user_email' => $user->email,
                'payment_request_id' => $paymentRequest->id
            ]);

            try {
                // Send notification to user via FCM
                $result = $this->firebaseService->sendNotificationToUser(
                    $user,
                    'New Payment Request',
                    "Payment request from {$request->name} for {$request->destination}",
                    $notificationData
                );

                if ($result['success']) {
                    \Log::info('FCM notification sent successfully', ['result' => $result]);
                } else {
                    \Log::warning('Failed to send FCM notification: ' . $result['error']);
                    // Jangan mengembalikan error 500 hanya karena notifikasi gagal
                    // Update payment request status to reflect notification failure
                    $paymentRequest->update(['status' => 'failed']);
                }
            } catch (\Exception $notificationException) {
                \Log::error('Exception occurred while sending FCM notification: ' . $notificationException->getMessage(), [
                    'exception' => $notificationException->getMessage(),
                    'trace' => $notificationException->getTraceAsString()
                ]);
                // Jangan mengembalikan error 500 hanya karena notifikasi gagal
                // Update payment request status to reflect notification failure
                $paymentRequest->update(['status' => 'failed']);
            }

            return new ApiResponseResource([
                'status' => true,
                'message' => 'Payment request sent successfully',
                'data' => [
                    'payment_request_id' => $paymentRequest->id,
                    'payment_details' => [
                        'name' => $request->name,
                        'id' => $request->id,
                        'destination' => $request->destination,
                        'price' => $request->price,
                        'email' => $request->email,
                    ],
                    'merchant_info' => [
                        'id' => $merchant->id,
                        'name' => $merchant->name,
                        'username' => $merchant->username
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in handlePaymentRequest: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return new ApiResponseResource([
                'status' => false,
                'message' => 'An error occurred while processing the payment request',
                'data' => [
                    'details' => $e->getMessage()
                ],
            ], 500);
        }
    }

    /**
     * Get merchant profile
     * This endpoint returns the authenticated merchant's profile information
     */
    public function MerchantProfile(Request $request)
    {
        // Validate API key from header
        $apiKey = $request->header('X-API-Key') ?: $request->header('API-Key') ?: $request->input('api_key');

        if (!$apiKey) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'API key is required',
                'data' => null
            ], 401);
        }

        // Find merchant by API key
        $merchant = Merchant::where('api_key', $apiKey)->first();

        if (!$merchant) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Unauthorized: Invalid API key',
                'data' => null
            ], 401);
        }

        // Return merchant profile information
        return new ApiResponseResource([
            'status' => true,
            'message' => 'Merchant profile retrieved successfully',
            'data' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'username' => $merchant->username,
                'email' => $merchant->email,
                'phone' => $merchant->phone,
                'business_name' => $merchant->business_name,
                'created_at' => $merchant->created_at,
                'updated_at' => $merchant->updated_at,
            ],
        ]);
    }
}