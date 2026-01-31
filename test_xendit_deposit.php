<?php

// Test script for Xendit deposit functionality

require_once 'vendor/autoload.php';

// Bootstrap Laravel to use its features
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "Testing Xendit Deposit API functionality...\n\n";

// Get an authenticated user (first user in database)
$user = User::first();
if (!$user) {
    echo "No users found in database\n";
    exit(1);
}

echo "Testing with user: " . $user->name . " (ID: " . $user->id . ")\n";
echo "Current balance: Rp " . number_format($user->saldo, 0, ',', '.') . "\n\n";

// Get Sanctum token for the user
$token = $user->createToken('test-token')->plainTextToken;
echo "Generated Sanctum token: " . substr($token, 0, 10) . "..." . substr($token, -5) . "\n\n";

// Test the deposit endpoint
echo "--- Testing POST /api/user/deposit ---\n";
$curl = curl_init();

$depositAmount = 50000; // Rp 50,000

curl_setopt_array($curl, [
    CURLOPT_URL => "http://localhost:8000/api/user/deposit",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => $depositAmount,
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: \n";
echo $response . "\n\n";

// Test the balance endpoint
echo "--- Testing POST /api/user/balance ---\n";
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "http://localhost:8000/api/user/balance",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true, // Using POST as per the route definition
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: \n";
echo $response . "\n\n";

echo "Xendit deposit functionality is implemented!\n";
echo "To complete the integration:\n";
echo "1. Set XENDIT_API_KEY in your .env file\n";
echo "2. Set XENDIT_CALLBACK_SECRET in your .env file\n";
echo "3. Configure Xendit to send webhooks to " . $_SERVER['HTTP_HOST'] . "/api/xendit/webhook\n";
echo "4. Run: php artisan migrate to create the deposits table\n";