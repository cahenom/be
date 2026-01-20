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

    // =============== SALDO TIDAK CUKUP ===============
    if ($user->saldo < $harga_jual) {

        // Insert transaksi manual tanpa request Digiflazz
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

        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $ref_id,
            'message' => 'Saldo tidak cukup',
            'data'    => [
                'saldo' => $user->saldo,
                'harga' => $harga_jual
            ]
        ]);
    }

    // =============== LANJUT DIGIFLAZZ (KALAU SALDO CUKUP) ===============
    $response = Http::withHeaders($this->header)->post($this->url . '/transaction', [
        "username"       => $this->user,
        "buyer_sku_code" => $request->sku,
        "customer_no"    => $request->customer_no,
        "ref_id"         => $ref_id,
        "sign"           => md5($this->user . $this->key . $ref_id)
    ]);

    $result = $response->json();
    $data   = $result['data'] ?? null;

    // Potong saldo jika sukses/pending
    if ($data && in_array($data['status'], ['Sukses', 'Pending'])) {
        DB::transaction(function () use ($user, $harga_jual) {
            $user->saldo -= $harga_jual;
            $user->save();
        });
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

    return new ApiResponseResource([
        'status'  => $data['status'] ?? 'error',
        'ref_id'  => $ref_id,
        'message' => $data['message'] ?? 'Transaksi gagal',
        'data'    => $data
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

        $response = Http::withHeaders($this->header)->post($this->url . '/transaction', [
            "commands"       => "inq-pasca",
            "username"       => $this->user,
            "buyer_sku_code" => $request->sku,
            "customer_no"    => $request->customer_no,
            "ref_id"         => $ref_id,
            "sign"           => md5($this->user . $this->key . $ref_id),
        ]);

        $result = $response->json();
        $data   = $result['data'] ?? null;

        return new ApiResponseResource([
            'status'  => $data['status'] ?? 'error',
            'ref_id'  => $ref_id,
            'message' => 'tagihan berhasil di cek',
            'data'    => $data
        ]);
    }


    /* =========================================================================
        BAYAR TAGIHAN PASCA
    ========================================================================= */
   public function digiflazBayarTagihan(Request $request)
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

    // Ambil produk pasca
    $product = ProductPasca::findBySKU($request->sku)->first();
    if (!$product) {
        return new ApiResponseResource([
            'status'  => 'error',
            'ref_id'  => $ref_id,
            'message' => 'SKU tidak ditemukan',
            'data'    => null,
        ]);
    }

    // Menggunakan harga admin + harga pasca
    $pricingService = new PricingService();
    $roleId = (int)($user->role_id ?? 1);

    $harga_modal = (float)$product->product_seller_price;

    $harga_jual = $pricingService->applyMarkup(
        $harga_modal,
        $roleId
    );

    // ================= SALDO TIDAK CUKUP =================
    if ($user->saldo < $harga_jual) {

        // Insert transaksi manual tanpa hit API Digiflazz
        $this->model_transaction->insert_transaction_data(
            [
                'ref_id'        => $ref_id,
                'customer_no'   => $request->customer_no,
                'buyer_sku_code'=> $request->sku,
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
            'ref_id'  => $ref_id,
            'message' => 'Saldo tidak cukup',
            'data'    => [
                'saldo' => $user->saldo,
                'harga' => $harga_jual
            ]
        ]);
    }

    // ================= SALDO CUKUP → PROSES DIGIFLAZZ =================
    $response = Http::withHeaders($this->header)->post($this->url . '/transaction', [
        "commands"       => "pay-pasca",
        "username"       => $this->user,
        "buyer_sku_code" => $request->sku,
        "customer_no"    => $request->customer_no,
        "ref_id"         => $ref_id,
        "sign"           => md5($this->user . $this->key . $ref_id),
    ]);

    $result = $response->json();
    $data   = $result['data'] ?? null;

    // Potong saldo jika status Pending atau Sukses
    if ($data && in_array($data['status'], ['Sukses', 'Pending'])) {
        DB::transaction(function () use ($user, $harga_jual) {
            $user->saldo -= $harga_jual;
            $user->save();
        });
    }

    // Insert transaksi pasca
    if ($data) {
        $this->model_transaction->insert_transaction_data(
            $data,
            'Pasca',
            $product->product_provider,
            $user->id,
            $harga_jual
        );
    }

    return new ApiResponseResource([
        'status'  => $data['status'] ?? 'error',
        'ref_id'  => $ref_id,
        'message' => $data['message'] ?? 'Pembayaran gagal',
        'data'    => $data
    ]);
}

}
