<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Deposit redirect routes
Route::get('/deposit/success', [App\Http\Controllers\DepositRedirectController::class, 'success'])->name('deposit.success');
Route::get('/deposit/failed', [App\Http\Controllers\DepositRedirectController::class, 'failed'])->name('deposit.failed');

