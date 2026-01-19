<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DigiflazController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DigiflazzWebhookController;
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


Route::post('/digiflazz/webhook', [DigiflazzWebhookController::class, 'handle']);


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

    Route::controller(ProductController::class)->prefix('product')->group(function () {
        Route::post('/pulsa', 'pulsa');
        Route::post('/emoney', 'emoney');
        Route::post('/games', 'games');
        Route::post('/masaaktif', 'masa_aktif');
        Route::post('/pln', 'pln');
        Route::post('/tv', 'tv');
        Route::post('/voucher', 'voucher');
        Route::post('/category', 'category');

    });
});
