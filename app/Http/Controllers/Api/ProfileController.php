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
                'name'      => 'sometimes|string|max:100',
                'email'     => 'sometimes|email|unique:users,email,' . $user->id,
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
            'produk' => $transaction->product ? $transaction->product->product_name : null,
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
}