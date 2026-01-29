<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DigiflazController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\PaymentRequestController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\TestFirebaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/digiflazz/webhook', [\App\Http\Controllers\Api\DigiflazzWebhookController::class, 'handle']);

Route::post(
    '/auth/login',
    [AuthController::class, 'AuthLogin']
);
Route::post(
    '/auth/register',
    [AuthController::class, 'AuthRegister']
);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post(
        '/auth/logout',
        [AuthController::class, 'AuthLogout']
    );

    Route::controller(ProfileController::class)->prefix('user')->group(function () {
        Route::post('/profile', 'profile');
        Route::post('/transaksi', 'transactions');
    });

    Route::controller(DigiflazController::class)->prefix('order')->group(function () {
        Route::post('/get-product-prepaid', 'get_product_prepaid');
        Route::post('/get-product-pasca', 'get_product_pasca');
        Route::post('/topup', 'digiflazTopup');
        Route::post('/cek-tagihan', 'digiflazCekTagihan');
        Route::post('/bayar-tagihan', 'digiflazBayarTagihan');
    });

    // Product endpoints with descriptions
    Route::controller(ProductController::class)->prefix('product')->group(function () {
        Route::post('/pulsa', 'pulsa');          // Get pulsa and data package products based on customer number
        Route::post('/emoney', 'emoney');        // Get e-money products
        Route::post('/games', 'games');          // Get game products
        Route::post('/masaaktif', 'masa_aktif'); // Get active period products
        Route::post('/pln', 'pln');              // Get PLN (electricity) products
        Route::post('/tv', 'tv');                // Get TV subscription products
        Route::post('/voucher', 'voucher');      // Get voucher products
        Route::post('/category', 'category');    // Get product category list
        Route::post('/pdam', 'pdam');            // Get PDAM products
        Route::post('/internet', 'internet'); // Get internet products
        Route::post('/bpjs', 'bpjs'); // Get BPJS products
    });

    // Firebase Cloud Messaging routes
    Route::post('/fcm/token', [FirebaseController::class, 'saveToken']); 
    
    // Payment request operations for users (require authentication)
    Route::get('/payment-requests/pending', [PaymentRequestController::class, 'getUserPendingRequests']); // Get pending payment requests for user
    Route::get('/payment-requests/{id}', [PaymentRequestController::class, 'showPaymentRequest']); // Get specific payment request
    Route::post('/payment-requests/{id}/approve', [PaymentRequestController::class, 'approvePaymentRequest']); // Approve a payment request
    Route::post('/payment-requests/{id}/reject', [PaymentRequestController::class, 'rejectPaymentRequest']); // Reject a payment request
});

Route::controller(MerchantController::class)->prefix('merchant')->group(function () {
    Route::post('/profile', 'MerchantProfile');
    Route::post('/request', 'HandlePaymentRequest'); // Send payment request to user
});