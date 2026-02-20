<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\PascaTransaction;
use App\Models\TransactionModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Ambil & update profil user
     */
    public function profile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        // Update profile jika POST + ada data
        if ($request->isMethod('post') && count($request->all()) > 0) {

            $validated = $request->validate([
                'name'      => ['sometimes', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.\-]+$/'],
                'email'     => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'phone'     => 'sometimes|string|max:15|unique:users,phone,' . $user->id,
            ], [
                'name.regex' => 'Nama hanya boleh mengandung huruf, angka, spasi, titik, dan strip.',
            ]);

            $user->update($validated);

            return new ApiResponseResource([
                'status'  => 'success',
                'message' => 'Profile berhasil didapatkan.',
                'data'    =>  $user,
            ]);
        }

        // Ambil profil user
        return new ApiResponseResource([
            'status'  => true,
            'message' => 'User profile fetched.',
            'data'    => $user,
        ]);
    }

    /**
     * Get user balance
     */
    public function balance(Request $request)
    {
        $user = $request->user(); // Sanctum auto detect

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        return new ApiResponseResource([
            'status' => true,
            'message' => 'Balance retrieved successfully.',
            'data' => [
                'balance' => $user->saldo,
            ]
        ]);
    }

    /**
     * Deposit balance to user wallet using Xendit Invoice
     */
    public function deposit(Request $request)
    {
        $user = $request->user(); // Sanctum auto detect

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1000', // Minimum deposit amount
        ]);

        $amount = $request->input('amount');
        $externalId = 'deposit_' . time() . '_' . $user->id;

        try {
            // Initialize Xendit client with API key from environment
            $xenditApiKey = env('XENDIT_API_KEY');
            if (!$xenditApiKey) {
                return new ApiResponseResource([
                    'status' => false,
                    'message' => 'Xendit API key not configured.',
                    'data' => null,
                ], 500);
            }

            // Create invoice using Xendit API
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://api.xendit.co/v2/invoices', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($xenditApiKey . ':'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'external_id' => $externalId,
                    'amount' => $amount,
                    'description' => 'Deposit to ' . $user->name . '\'s wallet',
                    'invoice_duration' => 86400, // 24 hours in seconds
                    'success_redirect_url' => env('APP_URL') . '/deposit/success',
                    'failure_redirect_url' => env('APP_URL') . '/deposit/failed',
                    'currency' => 'IDR',
                    'should_send_email' => false
                    // Removing payment_methods to use Xendit's default available payment methods
                ]
            ]);

            $invoiceData = json_decode($response->getBody(), true);

            // Create deposit record
            \App\Models\Deposit::create([
                'user_id' => $user->id,
                'external_id' => $externalId,
                'invoice_id' => $invoiceData['id'],
                'amount' => $amount,
                'status' => $invoiceData['status'],
                'payment_method' => 'xendit_invoice',
                'xendit_response' => $invoiceData,
            ]);

            return new ApiResponseResource([
                'status' => true,
                'message' => 'Deposit invoice created successfully.',
                'data' => [
                    'invoice_id' => $invoiceData['id'],
                    'external_id' => $externalId,
                    'amount' => $amount,
                    'payment_url' => $invoiceData['invoice_url'],
                    'expiry_date' => $invoiceData['expiry_date'],
                    'status' => $invoiceData['status'],
                    // Include payment methods if available in the response
                    'available_banks' => $invoiceData['available_banks'] ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Failed to create deposit invoice: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Ambil transaksi user (termasuk transaksi pasca bayar dan permintaan pembayaran)
     */
    public function transactions(Request $request)
    {
        $user = $request->user(); // Sanctum auto detect

        if (!$user) {
            return new ApiResponseResource([
                'status' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        // Ambil 20 transaksi terbaru user dari transaction table (more than needed for combination)
        $regularTransactions = TransactionModel::where('transaction_user_id', $user->id)
                                               ->with(['user', 'product']) // Eager load relasi user dan product untuk menghindari N+1 query
                                                ->orderBy('created_at', 'desc')
                                                ->limit(20)
                                                ->get();
                                               // ->remember(300) // Cache for 5 minutes - commented out for dynamic data

        // Ambil 20 transaksi pasca bayar terbaru dari pasca_transactions table (more than needed for combination)
        $pascaTransactions = PascaTransaction::where('user_id', $user->id)
                                                ->with('product') // Eager load relasi product untuk menghindari N+1 query
                                                ->orderBy('created_at', 'desc')
                                                ->limit(20)
                                             ->get(); // PascaTransaction tidak memiliki relasi user jadi tidak perlu eager loading

        // Ambil 20 permintaan pembayaran terbaru dari payment_requests table
        $paymentRequests = \App\Models\PaymentRequest::where('user_id', $user->id)
                                                     ->with('user') // Eager load relasi user untuk menghindari N+1 query
                                                     ->orderBy('created_at', 'desc')
                                                     ->limit(20)
                                                     ->get();

        // Gabungkan ketiga koleksi dan urutkan berdasarkan created_at terbaru
        $allTransactions = collect();

        foreach ($regularTransactions as $transaction) {
            $allTransactions->push([
                'ref' => $transaction->transaction_code,
                'tujuan' => $transaction->transaction_number,
                'sku' => $transaction->transaction_sku,
                'produk' => $transaction->transaction_product_name ?: ($transaction->product ? $transaction->product->product_name : null),
                'status' => $transaction->transaction_status,
                'message' => $transaction->transaction_message,
                'price' => $transaction->transaction_total,  // Using transaction_total from transaction record
                'sn' => $transaction->transaction_sn, // Use the SN field if available
                'type' => 'prepaid',
                'created_at' => $transaction->created_at,
            ]);
        }

        foreach ($pascaTransactions as $transaction) {
            $allTransactions->push([
                'ref' => $transaction->ref_id,
                'tujuan' => $transaction->customer_no,
                'sku' => $transaction->sku_code,
                'nama_produk' => $transaction->product ? $transaction->product->product_name : null,
                'status' => $transaction->status_payment ?: $transaction->status_inquiry,
                'message' => $transaction->message_payment ?: $transaction->message_inquiry,
                'price' => $transaction->amount_total,  // Using amount_total which should be the selling_price equivalent
                'sn' => $transaction->sn, // Use the SN field from pasca transaction
                'type' => 'postpaid',
                'created_at' => $transaction->created_at,
            ]);
        }

        // Tambahkan permintaan pembayaran ke dalam daftar transaksi
        foreach ($paymentRequests as $request) {
            $allTransactions->push([
                'ref' => $request->external_id,
                'tujuan' => $request->destination,
                'sku' => 'MERCHANT_REQUEST',
                'status' => $request->status,
                'message' => 'Payment request from ' . $request->name,
                'price' => $request->price,
                'sn' => null,
                'type' => 'merchant_request',
                'created_at' => $request->created_at,
                'internal_id' => $request->id, // Tambahkan ID internal untuk digunakan di frontend
            ]);
        }

        // Urutkan semua transaksi berdasarkan created_at terbaru, lalu ambil 10 teratas
        $allTransactions = $allTransactions->sortByDesc('created_at')->take(10)->values();

        return new ApiResponseResource([
            'status'  => true,
            'message' => 'User transactions berhasil didapatkan.',
            'data'    => $allTransactions,
        ]);
    }

    /**
     * Upgrade user to Reseller level (Role ID 2) for 200,000 saldo
     */
    public function upgradeToReseller(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        // Check if already a reseller or higher
        if ((int)$user->roles_id === 2) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Anda sudah menjadi Reseller.',
                'data' => null
            ], 400);
        }

        if ((int)$user->roles_id === 3) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Anda adalah Admin/Agen.',
                'data' => null
            ], 400);
        }

        $cost = 200000;
        if ($user->saldo < $cost) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Saldo tidak cukup. Dibutuhkan Rp ' . number_format($cost, 0, ',', '.'),
                'data' => null
            ], 400);
        }

        // Deduct balance and upgrade role
        $user->saldo -= $cost;
        $user->roles_id = 2;
        $user->save();

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Selamat! Anda berhasil upgrade ke Reseller.',
            'data' => $user
        ]);
    }

    /**
     * Search user by phone number (for transfer)
     */
    public function searchByPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
        ]);

        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first(['id', 'name', 'phone']);

        if (!$user) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'User tidak ditemukan.',
                'data' => null
            ], 404);
        }

        if ($user->id === Auth::id()) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Tidak bisa mencari diri sendiri.',
                'data' => null
            ], 400);
        }

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'User ditemukan.',
            'data' => $user
        ]);
    }
}