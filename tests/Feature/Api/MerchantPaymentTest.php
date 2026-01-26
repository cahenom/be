<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\PaymentRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MerchantPaymentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_merchant_can_send_payment_request()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'fcm_token' => 'test_fcm_token_12345',
            'roles_id' => 1, // user role
        ]);

        // Mock the Firebase service to avoid actual FCM calls during testing
        $this->mock(\App\Services\FirebaseService::class, function ($mock) {
            $mock->shouldReceive('sendNotificationToUser')
                 ->andReturn(['success' => true]);
        });

        $response = $this->postJson('/api/merchant', [
            'name' => 'Merchant Store',
            'id' => 'ORD-12345',
            'destination' => 'Product Purchase',
            'price' => 100000,
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'payment_request_id',
                     'payment_details' => [
                         'name',
                         'id',
                         'destination',
                         'price',
                         'email'
                     ]
                 ]);

        // Check that the payment request was stored in the database
        $this->assertDatabaseHas('payment_requests', [
            'external_id' => 'ORD-12345',
            'name' => 'Merchant Store',
            'destination' => 'Product Purchase',
            'price' => 100000,
            'email' => 'test@example.com',
            'status' => 'pending'
        ]);
    }

    public function test_user_can_get_pending_payment_requests()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'fcm_token' => 'test_fcm_token_12345',
            'roles_id' => 1, // user role
        ]);

        // Create a payment request associated with the user
        $paymentRequest = PaymentRequest::create([
            'external_id' => 'ORD-TEST',
            'name' => 'Test Store',
            'destination' => 'Test Product',
            'price' => 50000,
            'email' => 'test@example.com',
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/payment-requests/pending');

        $response->assertStatus(200)
                 ->assertJson([
                     'payment_requests' => [
                         [
                             'external_id' => 'ORD-TEST',
                             'name' => 'Test Store',
                             'destination' => 'Test Product',
                             'price' => 50000,
                             'status' => 'pending'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_approve_payment_request()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'fcm_token' => 'test_fcm_token_12345',
            'roles_id' => 1, // user role
        ]);

        // Create a payment request associated with the user
        $paymentRequest = PaymentRequest::create([
            'external_id' => 'ORD-APPROVE-TEST',
            'name' => 'Approve Test Store',
            'destination' => 'Approve Test Product',
            'price' => 75000,
            'email' => 'test@example.com',
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson("/api/payment-requests/{$paymentRequest->id}/approve");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Payment request approved successfully'
                 ]);

        // Refresh the model and check the status
        $paymentRequest->refresh();
        $this->assertEquals('approved', $paymentRequest->status);
    }

    public function test_user_can_reject_payment_request()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'fcm_token' => 'test_fcm_token_12345',
            'roles_id' => 1, // user role
        ]);

        // Create a payment request associated with the user
        $paymentRequest = PaymentRequest::create([
            'external_id' => 'ORD-REJECT-TEST',
            'name' => 'Reject Test Store',
            'destination' => 'Reject Test Product',
            'price' => 60000,
            'email' => 'test@example.com',
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson("/api/payment-requests/{$paymentRequest->id}/reject");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Payment request rejected successfully'
                 ]);

        // Refresh the model and check the status
        $paymentRequest->refresh();
        $this->assertEquals('rejected', $paymentRequest->status);
    }
}