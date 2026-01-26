<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Merchant;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample merchant
        Merchant::create([
            'name' => 'Sample Merchant',
            'username' => 'sample_merchant',
            'merchant_id' => 'M001',
            'email' => 'merchant@example.com',
            'webhook' => 'https://webhook.site/sample-webhook-url',
            'ip' => json_encode(['192.168.1.1', '10.0.0.1']), // Store as JSON array
            'password' => 'password123', // This will be hashed by the mutator
            'api_key' => Str::random(60), // Generate a random API key
        ]);
    }
}
