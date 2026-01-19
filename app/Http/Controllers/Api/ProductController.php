<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ProductResource;
use App\Models\PrefixNumber;
use App\Models\ProductPrepaid;
use App\Services\PricingService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** -----------------------------------------
     *  APPLY MARKUP (INTERNAL ONLY)
     * ------------------------------------------*/
    private function applyMarkupToProducts($products)
    {
        $roleId = auth()->user()->role_id ?? 1;
        $pricingService = new PricingService();

        return $products->map(function ($product) use ($pricingService, $roleId) {

            // hitung harga jual (markup %)
            $product->product_buyer_price = $pricingService->applyMarkup(
                $product->product_seller_price,
                $roleId
            );

            return $product;
        });
    }

    /* -----------------------------------------
        PULSA + PAKET DATA
    ------------------------------------------*/
    public function pulsa(Request $request)
    {
        $request->validate([
            'customer_no' => 'required|min:4'
        ]);

        $prefix = substr($request->customer_no, 0, 4);
        $provider = PrefixNumber::findProviderByNumber($prefix)->first();

        if (!$provider) {
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => null,
                'message' => 'Provider tidak ditemukan',
                'data'    => null
            ]);
        }

        $pulsa = $this->applyMarkupToProducts(
            ProductPrepaid::findProductByProvider($provider->provider, 'Pulsa')->get()
        );

        $paketData = $this->applyMarkupToProducts(
            ProductPrepaid::findProductByProvider($provider->provider, 'Data')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Produk pulsa & paket data ditemukan',
            'data' => [
                'provider' => $provider->provider,
                'pulsa' => ProductResource::collection($pulsa),
                'paket_data' => ProductResource::collection($paketData),
            ]
        ]);
    }

    /* -----------------------------------------
        E-MONEY
    ------------------------------------------*/
    public function emoney()
    {
        $products = $this->applyMarkupToProducts(
            ProductPrepaid::where('product_category', 'E-Money')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar E-Money ditemukan',
            'data' => [
                'emoney' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        GAMES
    ------------------------------------------*/
    public function games()
    {
        $products = $this->applyMarkupToProducts(
            ProductPrepaid::where('product_category', 'Games')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar produk Games ditemukan',
            'data' => [
                'games' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        VOUCHER
    ------------------------------------------*/
    public function voucher()
    {
        $products = $this->applyMarkupToProducts(
            ProductPrepaid::where('product_category', 'Voucher')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar Voucher ditemukan',
            'data' => [
                'voucher' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        PLN
    ------------------------------------------*/
    public function pln()
    {
        $products = $this->applyMarkupToProducts(
            ProductPrepaid::where('product_category', 'PLN')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar produk PLN ditemukan',
            'data' => [
                'pln' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        TV
    ------------------------------------------*/
    public function tv()
    {
        $products = $this->applyMarkupToProducts(
            ProductPrepaid::where('product_category', 'TV')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar produk TV ditemukan',
            'data' => [
                'tv' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        MASA AKTIF
    ------------------------------------------*/
    public function masa_aktif()
    {
        $products = $this->applyMarkupToProducts(
            ProductPrepaid::where('product_category', 'Masa Aktif')->get()
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar masa aktif ditemukan',
            'data' => [
                'masa_aktif' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        CATEGORY LIST
    ------------------------------------------*/
    public function category()
    {
        $category = ProductPrepaid::select('product_category')
            ->distinct()
            ->orderBy('product_category')
            ->get();

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar kategori produk',
            'data' => [
                'category' => $category
            ]
        ]);
    }
}
