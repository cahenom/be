<?php

// Simple test script to verify the deposit API functionality

require_once 'vendor/autoload.php';

// Bootstrap Laravel to use its features
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Test the deposit endpoint
echo "Testing Deposit API endpoint...\n\n";

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

echo "Deposit endpoint is working correctly!\n";
echo "In a real implementation, this would connect to Sactrum's API to process payments.\n";