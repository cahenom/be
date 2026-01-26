<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\RoleProfitSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MerchantApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the roles that are required for user creation
        $this->artisan('db:seed', ['--class' => RoleSeeder::class]);
        $this->artisan('db:seed', ['--class' => RoleProfitSeeder::class]);

        // Create a user that will receive the payment request
        User::factory()->create([
            'email' => 'test@example.com',
            'roles_id' => 1 // user role
        ]);
    }

    public function test_merchant_can_make_payment_request_with_valid_api_key()
    {
        // Create a merchant with a valid API key
        $merchant = Merchant::create([
            'name' => 'Test Merchant',
            'username' => 'test_merchant',
            'merchant_id' => 'M001',
            'email' => 'merchant@example.com',
            'webhook' => 'https://webhook.site/test',
            'ip' => json_encode(['192.168.1.1']),
            'password' => 'password123',
            'api_key' => 'valid_test_api_key_12345'
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => 'valid_test_api_key_12345',
        ])->postJson('/api/merchant', [
            'name' => 'Test Product',
            'id' => 'TEST001',
            'destination' => '081234567890',
            'price' => 50000,
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'message' => 'Payment request sent successfully'
                 ]);
    }

    public function test_merchant_cannot_make_payment_request_without_api_key()
    {
        $response = $this->postJson('/api/merchant', [
            'name' => 'Test Product',
            'id' => 'TEST002',
            'destination' => '081234567890',
            'price' => 50000,
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => false,
                     'message' => 'API key is required'
                 ]);
    }

    public function test_merchant_cannot_make_payment_request_with_invalid_api_key()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'invalid_api_key',
        ])->postJson('/api/merchant', [
            'name' => 'Test Product',
            'id' => 'TEST003',
            'destination' => '081234567890',
            'price' => 50000,
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => false,
                     'message' => 'Unauthorized: Invalid API key'
                 ]);
    }
}
