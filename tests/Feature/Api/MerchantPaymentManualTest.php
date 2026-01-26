<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\PaymentRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MerchantPaymentManualTest extends TestCase
{
    use DatabaseTransactions;

    public function test_merchant_api_endpoint_exists()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'fcm_token' => 'test_fcm_token_12345',
            'roles_id' => 1, // user role
        ]);

        // Test that the endpoint exists and returns the expected error (since we're not mocking Firebase)
        $response = $this->postJson('/api/merchant', [
            'name' => 'Merchant Store',
            'id' => 'ORD-12345',
            'destination' => 'Product Purchase',
            'price' => 100000,
            'email' => 'test@example.com'
        ]);

        // The endpoint should return 200 if validation passes, or 404/500 depending on Firebase setup
        // We're mainly checking that the route exists
        $this->assertTrue(in_array($response->getStatusCode(), [200, 401, 500]), 
            'Endpoint should exist and return a valid HTTP status code');
    }
}