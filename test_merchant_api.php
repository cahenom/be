<?php

// Simple test script to verify the merchant API functionality

// Get the API key from the database
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Merchant;

// Get the API key from the database
$merchant = Merchant::first();
if (!$merchant) {
    echo "No merchant found in database\n";
    exit(1);
}

$apiKey = $merchant->api_key;
echo "Using API key: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . "\n";

// Test the API endpoint
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "http://localhost:8000/api/merchant",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'name' => 'Test Product',
        'id' => 'TEST001',
        'destination' => '081234567890',
        'price' => 50000,
        'email' => 'test@example.com'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: \n";
echo $response . "\n";

// Test without API key
echo "\n--- Testing without API key ---\n";
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "http://localhost:8000/api/merchant",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'name' => 'Test Product',
        'id' => 'TEST002',
        'destination' => '081234567890',
        'price' => 50000,
        'email' => 'test@example.com'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: \n";
echo $response . "\n";

// Test with invalid API key
echo "\n--- Testing with invalid API key ---\n";
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "http://localhost:8000/api/merchant",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'name' => 'Test Product',
        'id' => 'TEST003',
        'destination' => '081234567890',
        'price' => 50000,
        'email' => 'test@example.com'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: invalid_api_key_here',
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: \n";
echo $response . "\n";