<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\TransactionModel;
use App\Http\Controllers\Api\DigiflazzWebhookController;
use Illuminate\Http\Request;

// 1. Setup Data
$user = User::first();
$initialBalance = $user->saldo;
$refId = 'TEST_REF_' . time();
$sellingPrice = 10500; // Original with markup

echo "INITIAL STATE:" . PHP_EOL;
echo "User ID: {$user->id} | Balance: " . number_format($initialBalance) . PHP_EOL;
echo "Creating test transaction for {$refId} with Total: " . number_format($sellingPrice) . PHP_EOL;

// 2. Create Transaction record manually
$trx = TransactionModel::create([
    'transaction_code'     => $refId,
    'transaction_date'     => now()->format('Y-m-d'),
    'transaction_time'     => now()->format('H:i:s'),
    'transaction_type'     => 'Prepaid',
    'transaction_provider' => 'TEST',
    'transaction_number'   => '08123456789',
    'transaction_sku'      => 'TEST_SKU',
    'transaction_message'  => 'Pending',
    'transaction_total'    => (int)$sellingPrice,
    'transaction_status'   => 'Pending',
    'transaction_sn'       => null,
    'transaction_user_id'  => $user->id
]);

// 3. Simulate Webhook "Gagal"
echo PHP_EOL . "SIMULATING WEBHOOK CALL (FAILURE)..." . PHP_EOL;

$payload = [
    'data' => [
        'ref_id' => $refId,
        'status' => 'Gagal',
        'message' => 'Test Failure',
        'price' => 10000, 
        'buyer_sku_code' => 'TEST_SKU'
    ]
];

$request = new Request([], [], [], [], [], [], json_encode($payload));
$request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($payload));

$controller = app(DigiflazzWebhookController::class);
$controller->handle($request);

// 4. Verify Final Balance
$freshUser = User::find($user->id);
$finalBalance = $freshUser->saldo;
$refundAmount = $finalBalance - $initialBalance;

echo PHP_EOL . "FINAL STATE:" . PHP_EOL;
echo "New Balance: " . number_format($finalBalance) . PHP_EOL;
echo "Refund Amount Detected: " . number_format($refundAmount) . PHP_EOL;

if (abs($refundAmount - $sellingPrice) < 0.01) {
    echo "SUCCESS ✅ - User was refunded the full selling price ($sellingPrice)!" . PHP_EOL;
} else {
    echo "FAILED ❌ - User only received $refundAmount, expected $sellingPrice" . PHP_EOL;
}

// Cleanup
$trx->delete();
$freshUser->saldo = $initialBalance;
$freshUser->save();
