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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

    $ref_id = $this->getCode();
    $user   = auth()->user();

    if (!$user) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $ref_id,
            'message' => 'User tidak login',
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
    $roleId = (int)($user->role_id ?? 1);
    $harga_modal = (float)$product->product_seller_price;

    $harga_jual = $pricingService->applyMarkup(
        $harga_modal,
        $roleId
    );

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
            $harga_jual
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

    // Potong saldo jika sukses/pending
    if ($data && in_array($data['status'], ['Sukses', 'Pending'])) {
        DB::transaction(function () use ($user, $harga_jual) {
            $user->saldo -= $harga_jual;
            $user->save();
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
            $harga_jual
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

        $this->model->insert_data($result['data']);

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

        $this->model_pasca->insert_data($result['data']);

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
                'price' => $data['price'] ?? 0,
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
        'ref_id'      => 'required|string',  // Changed to use ref_id instead of sku
        'customer_no' => 'required|string|min:6',
    ]);

    $payment_ref_id = $this->getCode(); // New reference ID for the payment
    $user   = auth()->user();

    if (!$user) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $payment_ref_id,
            'message' => 'User tidak login',
            'data'    => null,
        ]);
    }

    // Find the pasca transaction by ref_id and customer_no
    $pascaTransaction = \App\Models\PascaTransaction::where('ref_id', $request->ref_id)
                                                     ->where('customer_no', $request->customer_no)
                                                     ->first();

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
    $harga_jual = $pascaTransaction->amount_total;

    // ================= SALDO TIDAK CUKUP =================
    if ($user->saldo < $harga_jual) {

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
            $harga_jual
        );

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

    // Potong saldo terlebih dahulu sebelum melakukan pembayaran
    DB::transaction(function () use ($user, $harga_jual) {
        $user->saldo -= $harga_jual;
        $user->save();
    });

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

        // Update the payment status and message
        $pascaTransaction->update([
            'status_payment' => $paymentStatus,
            'message_payment' => $data['message'] ?? 'Pembayaran gagal',
        ]);

        // Insert transaksi pasca
        if ($data) {
            $this->model_transaction->insert_transaction_data(
                $data,
                'Pasca',
                $product->product_provider,
                $user->id,
                $harga_jual
            );
        } else {
            // If API response is empty, update the transaction status to failed
            $pascaTransaction->update([
                'status_payment' => 'failed',
                'message_payment' => 'Gagal mendapatkan respons dari server',
            ]);

            // Refund the balance since payment failed
            DB::transaction(function () use ($user, $harga_jual) {
                $user->saldo += $harga_jual;
                $user->save();
            });
        }
    } catch (\Exception $e) {
        // Log the actual error for debugging
        \Log::error('Digiflazz API connection error during payment: ' . $e->getMessage(), [
            'user_id' => $user->id ?? null,
            'sku' => $sku,
            'customer_no' => $request->customer_no,
            'ref_id' => $payment_ref_id
        ]);

        // If there's an exception during API call, refund the balance
        DB::transaction(function () use ($user, $harga_jual) {
            $user->saldo += $harga_jual;
            $user->save();
        });

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
        if (env('DIGIFLAZ_MODE') === 'development') {
            \Log::info($message, $context);
        }
    }

    /**
     * Helper method to conditionally log raw API responses based on environment
     */
    private function logRawApiResponse($message, $context = [])
    {
        if (env('DIGIFLAZ_MODE') === 'development') {
            \Log::info($message, $context);
        }
    }

    /**
     * Helper method to conditionally log API calls based on environment
     */
    private function logApiCall($message, $context = [])
    {
        if (env('DIGIFLAZ_MODE') === 'development') {
            \Log::info($message, $context);
        }
    }

}
