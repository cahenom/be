<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\ProductPasca;
use App\Models\ProductPrepaid;
use App\Models\TransactionModel;
use App\Services\PricingService;
use App\Traits\CodeGenerate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazController extends Controller
{
    use CodeGenerate;

    protected $header;
    protected $url;
    protected $user;
    protected $key;
    protected $model;
    protected $model_pasca;
    protected $model_transaction;

    public function __construct()
    {
        $this->header = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $this->url  = env('DIGIFLAZ_URL');
        $this->user = env('DIGIFLAZ_USER');
        $this->key  = env('DIGIFLAZ_MODE') == 'development'
            ? env('DIGIFLAZ_DEV_KEY')
            : env('DIGIFLAZ_PROD_KEY');

        // Log the URL configuration for debugging (only in development)
        $this->logInfo('Digiflaz API Configuration:', [
            'url' => $this->url,
            'user' => $this->user,
            'key' => $this->key ? 'SET' : 'NOT SET',
        ]);

        $this->model             = new ProductPrepaid();
        $this->model_pasca       = new ProductPasca();
        $this->model_transaction = new TransactionModel();
    }


    /* =========================================================================
        TOPUP PREPAID — APPLY MARKUP + SALDO CHECK + POTONG SALDO + HIT API
    ========================================================================= */
    public function digiflazTopup(Request $request)
{
    $request->validate([
        'sku'         => 'required|string',
        'customer_no' => 'required|string|min:6',
    ]);

    $user   = auth()->user();
    $ref_id = $this->getCode();

    if (!$user) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $ref_id,
            'message' => 'User tidak login',
            'data'    => null,
        ]);
    }

    // 🛡️ ATOMIC LOCK: Prevent concurrent double-spend for same target+sku
    $lockKey = "topup_lock_{$user->id}_{$request->sku}_{$request->customer_no}";
    $lock = Cache::lock($lockKey, 30); // 30 second timeout

    if (!$lock->get()) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $ref_id,
            'message' => 'Transaksi sedang diproses. Mohon tunggu sebentar.',
            'data'    => null,
        ]);
    }

    try {
        // Cek apakah ada transaksi serupa yang masih dalam proses
        $recentTransaction = $this->model_transaction->checkRecentTransaction(
            $request->customer_no,
            $request->sku,
            $user->id
        );

        if ($recentTransaction) {
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => $ref_id,
                'message' => 'Transaksi untuk nomor dan produk ini masih dalam proses. Mohon tunggu hingga selesai.',
                'data'    => null,
            ]);
        }

    // Ambil produk
    $product = ProductPrepaid::findProductBySKU($request->sku)->first();
    if (!$product) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $ref_id,
            'message' => 'SKU tidak ditemukan',
            'data'    => null,
        ]);
    }

    // Hitung harga
    $pricingService = new PricingService();
    $roleId = (int)($user->roles_id ?? 1);
    $harga_modal = (float)$product->product_seller_price;

    $harga_jual = $pricingService->applyMarkup(
        $harga_modal,
        $roleId
    );

    // Apply points discount if requested
    $pointsToDeduct = 0;
    if ($request->boolean('use_points')) {
        $pointsToDeduct = min($user->points, $harga_jual);
        $harga_jual -= $pointsToDeduct;
    }

    // =============== SELALU LANJUTKAN REQUEST, TAPI CEK DULU SALDO ===============
    $hasSufficientBalance = $user->saldo >= $harga_jual;

    if (!$hasSufficientBalance) {
        // Insert transaksi manual karena saldo tidak cukup
        $this->model_transaction->insert_transaction_data(
            [
                'ref_id'        => $ref_id,
                'customer_no'   => $request->customer_no,
                'buyer_sku_code'=> $request->sku,
                'message'       => 'Saldo tidak cukup',
                'status'        => 'Gagal' // status manual
            ],
            'Prepaid',
            $product->product_provider,
            $user->id,
            $harga_jual,
            $product->product_name,
            $product->product_category
        );

        // Return response for insufficient balance
        return new ApiResponseResource([
            'status'  => 'error',
            'message' => 'Saldo tidak cukup',
            'data'    => [
                'ref' => $ref_id,
                'tujuan' => $request->customer_no,
                'sku' => $request->sku,
                'status' => 'Gagal',
                'message' => 'Saldo tidak cukup',
                'price' => 0,
                'sn' => null,
            ],
            'code'    => 200
        ]);
    }

    // Log the URL being called for debugging
    $this->logApiCall('Digiflaz API topup call:', [
        'full_url' => $this->url . '/transaction',
        'url_base' => $this->url,
        'endpoint' => '/transaction',
        'user_id' => $user->id ?? null,
        'sku' => $request->sku,
        'customer_no' => $request->customer_no,
        'ref_id' => $ref_id,
    ]);

    // =============== LANJUT DIGIFLAZZ (KALAU SALDO CUKUP) ===============
    $response = Http::withHeaders($this->header)->post($this->url . '/transaction', [
        "username"       => $this->user,
        "buyer_sku_code" => $request->sku,
        "customer_no"    => $request->customer_no,
        "ref_id"         => $ref_id,
        "sign"           => md5($this->user . $this->key . $ref_id)
    ]);

    // Log the raw API response for debugging
    $this->logRawApiResponse('Digiflaz API raw topup response:', [
        'user_id' => $user->id ?? null,
        'sku' => $request->sku,
        'customer_no' => $request->customer_no,
        'ref_id' => $ref_id,
        'status_code' => $response->status(),
        'raw_response' => $response->body(),
    ]);

    $result = $response->json();
    $data   = $result['data'] ?? null;

        $points_awarded = false;
        if ($data && in_array($data['status'], ['Sukses', 'Pending'])) {
            // 🔒 CONSISTENCY CHECK: Ensure the response data matches our request
            // Digiflazz ref_id collision can return old data for a different target/sku
            $respCustomerNo = $data['customer_no'] ?? '';
            $respSku = $data['buyer_sku_code'] ?? '';

            if ($respCustomerNo !== $request->customer_no || $respSku !== $request->sku) {
                Log::error('Digiflazz Response Mismatch (RefID Collision?):', [
                    'expected' => ['no' => $request->customer_no, 'sku' => $request->sku],
                    'received' => ['no' => $respCustomerNo, 'sku' => $respSku],
                    'ref_id' => $ref_id
                ]);

                return new ApiResponseResource([
                    'status'  => 'error',
                    'message' => 'Terjadi gangguan sistem (ID Collision). Silakan coba lagi.',
                    'data'    => null,
                    'code'    => 400
                ]);
            }

            $points_awarded = DB::transaction(function () use ($user, $harga_jual, $data, $pointsToDeduct) {
                // Reload user with lock to prevent race conditions
                $freshUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                
                // Deduct balance and points
                $freshUser->saldo -= $harga_jual;
                if ($pointsToDeduct > 0) {
                    $freshUser->points -= $pointsToDeduct;
                }
                
                // Award points if status is Sukses
                $awarded = false;
                if ($data && $data['status'] === 'Sukses') {
                    $pointService = new \App\Services\PointService();
                    $points = $pointService->calculatePoints($harga_jual); // Earn points on the amount paid
                    $freshUser->points += $points;
                    $awarded = true;
                }
                
                $freshUser->save();
                return $awarded;
            });
        } else if ($data && $data['status'] === 'Gagal') {
        // If transaction failed, make sure to refund or handle appropriately
        // In topup case, failed transactions typically don't charge the user
    }

    // Insert transaksi
    if ($data) {
        $this->model_transaction->insert_transaction_data(
            $data,
            'Prepaid',
            $product->product_provider,
            $user->id,
            $harga_jual,
            $product->product_name,
            $product->product_category,
            $harga_modal,
            $points_awarded
        );
    }

    // Prepare simplified response data for topup
    $simplifiedTopupData = null;
    if ($data) {
        $simplifiedTopupData = [
            'ref' => $data['ref_id'] ?? $ref_id,
            'tujuan' => $data['customer_no'] ?? $request->customer_no,
            'sku' => $data['buyer_sku_code'] ?? $request->sku,
            'status' => $data['status'] ?? 'failed',
            'message' => $data['message'] ?? 'Transaksi gagal',
            'price' => $data['price'] ?? 0,
            'sn' => $data['sn'] ?? null,
        ];
    }

        return new ApiResponseResource([
            'status'  => $data['status'] ?? 'error',
            'message' => $data['message'] ?? 'Transaksi gagal',
            'data'    => $simplifiedTopupData,
            'code'    => 200
        ]);
    } finally {
        $lock->release();
    }
}




    /* =========================================================================
        GET PRODUCT PREPAID
    ========================================================================= */
    public function get_product_prepaid()
    {
        $response = Http::withHeaders($this->header)->post($this->url . '/price-list', [
            "cmd"      => "prepaid",
            "username" => $this->user,
            "sign"     => md5($this->user . $this->key . "pricelist")
        ]);

        $result = $response->json();

        if (isset($result['status']) && $result['status'] !== 'success') {
            return new ApiResponseResource([
                'status'  => 'error',
                'message' => $result['message'] ?? 'Gagal mengambil produk prepaid',
                'data'    => $result,
                'ref_id'  => null
            ]);
        }

        if (!isset($result['data'])) {
            return new ApiResponseResource([
                'status'  => 'error',
                'message' => 'Data produk prepaid tidak valid',
                'data'    => $result,
                'ref_id'  => null
            ]);
        }

        // Delete existing prepaid products before inserting new ones
        ProductPrepaid::truncate();

        $this->model->insert_data($result['data']);
        
        // Force clear cache after update
        ProductPrepaid::clearRelatedCache();

        return new ApiResponseResource([
            'status'  => 'success',
            'message' => 'Produk prepaid berhasil diperbarui',
            'data'    => [
                'total' => count($result['data'])
            ],
            'ref_id'  => null
        ]);
    }


    /* =========================================================================
        GET PRODUCT PASCA
    ========================================================================= */
    public function get_product_pasca()
    {
        $response = Http::withHeaders($this->header)->post($this->url . '/price-list', [
            "cmd"      => "pasca",
            "username" => $this->user,
            "sign"     => md5($this->user . $this->key . "pricelist")
        ]);

        $result = $response->json();

        if (isset($result['status']) && $result['status'] !== 'success') {
            return new ApiResponseResource([
                'status'  => 'error',
                'message' => $result['message'] ?? 'Gagal mengambil produk pasca',
                'data'    => $result,
                'ref_id'  => null
            ]);
        }

        if (!isset($result['data'])) {
            return new ApiResponseResource([
                'status'  => 'error',
                'message' => 'Data produk pasca tidak valid',
                'data'    => $result,
                'ref_id'  => null
            ]);
        }

        // Delete existing postpaid products before inserting new ones
        ProductPasca::truncate();

        $this->model_pasca->insert_data($result['data']);
        
        // Force clear cache after update
        ProductPasca::clearRelatedCache();

        return new ApiResponseResource([
            'status'  => 'success',
            'message' => 'Produk pasca berhasil diperbarui',
            'data'    => [
                'total' => count($result['data'])
            ],
            'ref_id'  => null
        ]);
    }


    /* =========================================================================
        CEK TAGIHAN PASCA
    ========================================================================= */
    public function digiflazCekTagihan(Request $request)
    {
        $request->validate([
            'sku'         => 'required|string',
            'customer_no' => 'required|string|min:6',
        ]);

        $ref_id = $this->getCode();
        $user = auth()->user();

        if (!$user) {
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => $ref_id,
                'message' => 'User tidak login',
                'data'    => null,
            ]);
        }

        // Create a record in pasca_transactions with pending status
        $pascaTransaction = \App\Models\PascaTransaction::create([
            'ref_id' => $ref_id,
            'user_id' => $user->id,
            'sku_code' => $request->sku,
            'customer_no' => $request->customer_no,
            'status_inquiry' => 'pending',
            'status_payment' => 'none',
        ]);

        try {
            // Log the URL being called for debugging
            $this->logApiCall('Digiflaz API inquiry call:', [
                'full_url' => $this->url . '/transaction',
                'url_base' => $this->url,
                'endpoint' => '/transaction',
                'user_id' => $user->id ?? null,
                'sku' => $request->sku,
                'customer_no' => $request->customer_no,
                'ref_id' => $ref_id,
            ]);

            $response = Http::withHeaders($this->header)->post($this->url . '/transaction', [
                "commands"       => "inq-pasca",
                "username"       => $this->user,
                "buyer_sku_code" => $request->sku,
                "customer_no"    => $request->customer_no,
                "ref_id"         => $ref_id,
                "sign"           => md5($this->user . $this->key . $ref_id),
            ]);

            // Log the raw API response before any processing
            $this->logRawApiResponse('Digiflazz API raw inquiry response:', [
                'user_id' => $user->id ?? null,
                'sku' => $request->sku,
                'customer_no' => $request->customer_no,
                'ref_id' => $ref_id,
                'status_code' => $response->status(),
                'raw_response' => $response->body(),
            ]);

            $result = $response->json();
            $data   = $result['data'] ?? null;

            // Update the pasca_transaction record with response data
            if ($data) {
                // Map API status to our enum values
                $apiStatus = $data['status'] ?? 'failed';
                $status = $this->mapApiStatusToEnum($apiStatus);

                // Extract denda (fine/penalty) from the detail array if available
                $denda = 0;
                if (isset($data['desc']['detail']) && is_array($data['desc']['detail'])) {
                    foreach ($data['desc']['detail'] as $detail) {
                        if (isset($detail['denda'])) {
                            $denda += (int)$detail['denda'];
                        }
                    }
                }

                $pascaTransaction->update([
                    'status_inquiry' => $status,
                    'customer_name' => $data['customer_name'] ?? null,
                    'total_periode' => $data['desc']['lembar_tagihan'] ?? 1,
                    'amount_bill' => $data['price'] ?? 0,
                    'amount_admin' => $data['admin'] ?? 0,
                    'amount_denda' => $denda,
                    'amount_total' => $data['selling_price'] ?? ($data['price'] ?? 0) + ($data['admin'] ?? 0) + $denda,
                    'periode' => $data['periode'] ?? null,
                    'daya' => $data['desc']['daya'] ?? null,
                    'gol_tarif' => $data['desc']['tarif'] ?? null,
                    'message_inquiry' => $data['message'] ?? null,
                ]);
            } else {
                $pascaTransaction->update([
                    'status_inquiry' => 'failed',
                    'message_inquiry' => 'Gagal mendapatkan data tagihan',
                ]);
            }
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Digiflazz API connection error during inquiry: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'sku' => $request->sku,
                'customer_no' => $request->customer_no,
                'ref_id' => $ref_id
            ]);

            // Update the pasca_transaction record with error status
            $pascaTransaction->update([
                'status_inquiry' => 'failed',
                'message_inquiry' => 'Gagal terhubung ke server pembayaran, silakan coba lagi nanti',
            ]);

            // Return error response
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => $ref_id,
                'message' => 'Gagal terhubung ke server pembayaran, silakan coba lagi nanti',
                'data'    => null
            ]);
        }

        // Prepare simplified response data
        $simplifiedData = null;
        if ($data) {
            $simplifiedData = [
                'ref_id' => $data['ref_id'] ?? $ref_id,
                'customer_no' => $data['customer_no'] ?? $request->customer_no,
                'customer_name' => $data['customer_name'] ?? null,
                'status' => $data['status'] ?? 'failed',
                'message' => $data['message'] ?? null,
                'selling_price' => $data['selling_price'] ?? 0,
                'admin' => $data['admin'] ?? 0,
                'periode' => $data['periode'] ?? null,
                'desc' => [
                    'tarif' => $data['desc']['tarif'] ?? null,
                    'daya' => $data['desc']['daya'] ?? null,
                    'lembar_tagihan' => $data['desc']['lembar_tagihan'] ?? null,
                ]
            ];
        }

        return new ApiResponseResource([
            'status'  => $data['status'] ?? 'error',
            'ref_id'  => $ref_id,
            'message' => 'tagihan berhasil di cek',
            'data'    => $simplifiedData
        ]);
    }


    /* =========================================================================
        BAYAR TAGIHAN PASCA
    ========================================================================= */
   public function digiflazBayarTagihan(Request $request)
{
    $request->validate([
        'sku' => 'required|string',
        'customer_no' => 'required|string|min:6',
    ]);

    $payment_ref_id = $this->getCode(); // Generate new reference ID for the payment
    $user   = auth()->user();

    if (!$user) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'User tidak login',
            'data'    => null,
        ]);
    }

    // 🛡️ ATOMIC LOCK: Prevent concurrent double-spend for same target+sku
    $lockKey = "payment_lock_{$user->id}_{$request->sku}_{$request->customer_no}";
    $lock = Cache::lock($lockKey, 30); // 30 second timeout

    if (!$lock->get()) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'Pembayaran sedang diproses. Mohon tunggu sebentar.',
            'data'    => null,
        ]);
    }

    try {
        // Cek apakah ada transaksi serupa yang masih dalam proses
        $recentTransaction = $this->model_transaction->checkRecentTransaction(
            $request->customer_no,
            $request->sku,
            $user->id
        );

        if ($recentTransaction) {
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => $payment_ref_id,
                'message' => 'Transaksi untuk nomor dan produk ini masih dalam proses. Mohon tunggu hingga selesai.',
                'data'    => null,
            ]);
        }

    // Find the pasca transaction by sku and customer_no (for internal inquiries)
    $pascaTransaction = \App\Models\PascaTransaction::where('sku_code', $request->sku)
                                                     ->where('customer_no', $request->customer_no)
                                                     ->first();

    // If not found, it might be an external inquiry where the customer_no is the kode_bayar
    // In this case, we need to create a temporary record or find another way to process
    // For external payments/tagihan that might not have a local inquiry record
    if (!$pascaTransaction) {
        // We still need to allow payment if the parameters are valid
        // But we should probably look up the product first to get the provider
        $product = ProductPasca::findBySKU($request->sku)->first();
        if (!$product) {
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => $payment_ref_id,
                'message' => 'SKU tidak ditemukan atau tagihan belum di-cek',
                'data'    => null,
            ]);
        }
        
        // Create a temporary pasca transaction record to keep track
        $pascaTransaction = \App\Models\PascaTransaction::create([
            'ref_id'        => $payment_ref_id,
            'user_id'       => $user->id,
            'sku_code'      => $request->sku,
            'customer_no'   => $request->customer_no,
            'status_inquiry'=> 'success', // Assigned for external flow
            'status_payment'=> 'pending',
            'amount_total'  => 0, // Will be updated from API response if possible
        ]);
    }

    // For internal transactions, continue with the normal flow
    if (!$pascaTransaction) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'Tagihan tidak ditemukan',
            'data'    => null,
        ]);
    }

    // Check if the transaction has already been paid successfully
    if ($pascaTransaction->status_payment === 'success') {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'Tagihan sudah pernah dibayar',
            'data'    => null,
        ]);
    }

    // Get the SKU from the pasca transaction record
    $sku = $pascaTransaction->sku_code;

    // Ambil produk pasca
    $product = ProductPasca::findBySKU($sku)->first();
    if (!$product) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'SKU tidak ditemukan',
            'data'    => null,
        ]);
    }

    // Use the amount_total from the stored pasca transaction record
    $harga_jual = $pascaTransaction->amount_total ?: 0; // Use 0 if not set

    // Apply points discount if requested
    $pointsToDeduct = 0;
    if ($request->boolean('use_points')) {
        $pointsToDeduct = min($user->points, $harga_jual);
        $harga_jual -= $pointsToDeduct;
    }

    // =============== SELALU LANJUTKAN REQUEST, TAPI CEK DULU SALDO ===============
    $hasSufficientBalance = $user->saldo >= $harga_jual;

    if (!$hasSufficientBalance) {
        // Update the pasca transaction status to failed
        $pascaTransaction->update([
            'status_payment' => 'failed',
            'message_payment' => 'Saldo tidak cukup',
        ]);

        // Insert transaksi manual tanpa hit API Digiflazz
        $this->model_transaction->insert_transaction_data(
            [
                'ref_id'        => $payment_ref_id,
                'customer_no'   => $request->customer_no,
                'buyer_sku_code'=> $sku,
                'message'       => 'Saldo tidak cukup',
                'status'        => 'Gagal'
            ],
            'Pasca',
            $product->product_provider,
            $user->id,
            $harga_jual,
            null, // product_name
            null, // product_category
            0,    // modal (not available for pasca yet)
            $points_awarded
        );

        // Return response for insufficient balance
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'Saldo tidak cukup',
            'data'    => [
                'saldo' => $user->saldo,
                'harga' => $harga_jual
            ]
        ]);
    }

    // =============== LANJUT DIGIFLAZZ (KALAU SALDO CUKUP) ===============
    // 💡 FIX: Jangan potong saldo dulu. Potong setelah dapat respon Sukses/Pending dari API.

    try {
        // Log the URL being called for debugging
        $this->logApiCall('Digiflaz API payment call:', [
            'full_url' => $this->url . '/transaction',
            'url_base' => $this->url,
            'endpoint' => '/transaction',
            'user_id' => $user->id ?? null,
            'sku' => $sku,
            'customer_no' => $request->customer_no,
            'ref_id' => $payment_ref_id,
        ]);

        // ================= PROSES DIGIFLAZZ =================
        $response = Http::withHeaders($this->header)->post($this->url . '/transaction', [
            "commands"       => "pay-pasca",
            "username"       => $this->user,
            "buyer_sku_code" => $sku,  // Use SKU from the pasca transaction record
            "customer_no"    => $request->customer_no,
            "ref_id"         => $payment_ref_id,  // Use the new payment reference ID
            "sign"           => md5($this->user . $this->key . $payment_ref_id),
        ]);

        // Log the raw API response before any processing
        $this->logRawApiResponse('Digiflazz API raw payment response:', [
            'user_id' => $user->id ?? null,
            'sku' => $sku,
            'customer_no' => $request->customer_no,
            'ref_id' => $payment_ref_id,
            'status_code' => $response->status(),
            'raw_response' => $response->body(),
        ]);

        $result = $response->json();
        $data   = $result['data'] ?? null;

        // Update the pasca transaction status based on response
        $paymentStatus = $this->mapApiStatusToEnum($data['status'] ?? 'failed');

        // 💰 BIG FIX: Potong saldo HANYA JIKA respon API adalah Sukses atau Pending
        if ($data && in_array($data['status'], ['Sukses', 'Pending'])) {
            // 🔒 CONSISTENCY CHECK: Ensure the response data matches our request
            $respCustomerNo = $data['customer_no'] ?? '';
            $respSku = $data['buyer_sku_code'] ?? '';

            if ($respCustomerNo !== $request->customer_no || $respSku !== $sku) {
                Log::error('Digiflazz Pasca Response Mismatch (RefID Collision?):', [
                    'expected' => ['no' => $request->customer_no, 'sku' => $sku],
                    'received' => ['no' => $respCustomerNo, 'sku' => $respSku],
                    'ref_id' => $payment_ref_id
                ]);

                return new ApiResponseResource([
                    'status'  => 'error',
                    'message' => 'Terjadi gangguan sistem (ID Collision). Silakan coba lagi.',
                    'data'    => null,
                    'code'    => 400
                ]);
            }

            $points_awarded = DB::transaction(function () use ($user, $harga_jual, $data, $pointsToDeduct) {
                // Reload user with lock to prevent race conditions
                $freshUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                
                // Deduct balance and points
                $freshUser->saldo -= $harga_jual;
                if ($pointsToDeduct > 0) {
                    $freshUser->points -= $pointsToDeduct;
                }
                
                // Award points if status is Sukses
                $awarded = false;
                if ($data && $data['status'] === 'Sukses') {
                    $pointService = new \App\Services\PointService();
                    $points = $pointService->calculatePoints($harga_jual); // Earn points on the amount paid
                    $freshUser->points += $points;
                    $awarded = true;
                }
                
                $freshUser->save();
                return $awarded;
            });
        }

        // Update the payment status and message
        $pascaTransaction->update([
            'status_payment' => $paymentStatus,
            'message_payment' => $data['message'] ?? 'Pembayaran gagal',
            'amount_total' => $data['selling_price'] ?? $harga_jual,
        ]);

        // Insert transaksi pasca
        if ($data) {
            $this->model_transaction->insert_transaction_data(
                $data,
                'Pasca',
                $product->product_provider,
                $user->id,
                $harga_jual,
                null, // product_name
                null, // product_category
                0,    // modal
                $points_awarded
            );
        } else {
            // If API response is empty, update the transaction status to failed
            $pascaTransaction->update([
                'status_payment' => 'failed',
                'message_payment' => 'Gagal mendapatkan respons dari server',
            ]);
        }
    } catch (\Exception $e) {
        // Log the actual error for debugging
        \Log::error('Digiflazz API connection error during payment: ' . $e->getMessage(), [
            'user_id' => $user->id ?? null,
            'sku' => $sku,
            'customer_no' => $request->customer_no,
            'ref_id' => $payment_ref_id
        ]);

        // 💡 FIX: Tidak perlu refund di sini karena saldo belum dipotong.
        // Update the pasca transaction status to failed
        $pascaTransaction->update([
            'status_payment' => 'failed',
            'message_payment' => 'Gagal terhubung ke server pembayaran, silakan coba lagi nanti',
        ]);

        // Return error response
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'Gagal terhubung ke server pembayaran, silakan coba lagi nanti',
            'data'    => null
        ]);
    }

    // Prepare simplified response data for payment
    $simplifiedPaymentData = null;
    if ($data) {
        $simplifiedPaymentData = [
            'ref_id' => $data['ref_id'] ?? $payment_ref_id,
            'customer_no' => $data['customer_no'] ?? $request->customer_no,
            'customer_name' => $data['customer_name'] ?? null,
            'status' => $data['status'] ?? 'failed',
            'message' => $data['message'] ?? 'Pembayaran gagal',
            'price' => $data['price'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'admin' => $data['admin'] ?? 0,
            'periode' => $data['periode'] ?? null,
        ];
    }

        return new ApiResponseResource([
            'status'  => $data['status'] ?? 'error',
            'ref_id'  => $payment_ref_id,  // Return the payment reference ID
            'message' => $data['message'] ?? 'Pembayaran gagal',
            'data'    => $simplifiedPaymentData
        ]);
    } finally {
        $lock->release();
    }
}

    /**
     * Map API status to our enum values
     */
    private function mapApiStatusToEnum($apiStatus)
    {
        $apiStatus = strtolower($apiStatus);

        switch ($apiStatus) {
            case 'sukses':
            case 'success':
                return 'success';
            case 'pending':
            case 'proses':
                return 'pending';
            case 'gagal':
            case 'failed':
            case 'error':
            default:
                return 'failed';
        }
    }

    /**
     * Handle Digiflazz response codes
     */
    private function handleDigiflazzResponseCode($rc)
    {
        // Common Digiflazz response codes
        $descriptions = [
            '00' => 'Transaksi Berhasil',
            '01' => 'Deposit tidak mencukupi',
            '02' => 'Deposit terpotong, tunggu konfirmasi CS',
            '03' => 'Transaksi Pending',
            '04' => 'Transaksi Gagal',
            '12' => 'Parameter tidak valid',
            '13' => 'Format salah',
            '14' => 'Nomor tidak valid',
            '15' => 'Nomor tidak terdaftar',
        ];

        return $descriptions[$rc] ?? 'Kode respon tidak dikenali';
    }

    /**
     * Helper method to conditionally log information based on environment
     */
    private function logInfo($message, $context = [])
    {
        $mode = env('DIGIFLAZ_MODE', 'production'); // default to production if not set
        if ($mode === 'development') {
            \Log::info($message, $context);
        }
    }

    /**
     * Helper method to conditionally log raw API responses based on environment
     */
    private function logRawApiResponse($message, $context = [])
    {
        $mode = env('DIGIFLAZ_MODE', 'production'); // default to production if not set
        if ($mode === 'development') {
            \Log::info($message, $context);
        }
    }

    /**
     * Helper method to conditionally log API calls based on environment
     */
    private function logApiCall($message, $context = [])
    {
        $mode = env('DIGIFLAZ_MODE', 'production'); // default to production if not set
        if ($mode === 'development') {
            \Log::info($message, $context);
        }
    }

}
