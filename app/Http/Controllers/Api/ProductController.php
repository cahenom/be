<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ProductResource;
use App\Models\PrefixNumber;
use App\Models\ProductPrepaid;
use App\Models\ProductPasca;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // Cache provider lookup for 1 hour
        $cacheKey = "provider_by_prefix_{$prefix}";
        $provider = Cache::remember($cacheKey, 3600, function () use ($prefix) {
            return PrefixNumber::findProviderByNumber($prefix)->first();
        });

        if (!$provider) {
            return new ApiResponseResource([
                'status'  => 'error',
                'ref_id'  => null,
                'message' => 'Provider tidak ditemukan',
                'data'    => null
            ]);
        }

        // Cache produk pulsa untuk provider ini
        $pulsa = $this->applyMarkupToProducts(
            Cache::remember("products_pulsa_{$provider->provider}", 1800, function () use ($provider) {
                return ProductPrepaid::findProductByProvider($provider->provider, 'Pulsa')->get();
            })
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Produk pulsa ditemukan',
            'data' => [
                'provider' => $provider->provider,
                'pulsa' => ProductResource::collection($pulsa),
            ]
        ]);
    }

    /* -----------------------------------------
        E-WALLET / E-MONEY (DATA)
    ------------------------------------------*/
    public function data()
    {
        $products = $this->applyMarkupToProducts(
            Cache::remember('products_data', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'Data')->get();
            })
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar Data ditemukan',
            'data' => [
                'data' => ProductResource::collection($products)
            ]
        ]);
    }

    /* -----------------------------------------
        E-MONEY
    ------------------------------------------*/
    public function emoney()
    {
        $products = $this->applyMarkupToProducts(
            Cache::remember('products_emoney', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'E-Money')->get();
            })
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
            Cache::remember('products_games', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'Games')->get();
            })
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
            Cache::remember('products_voucher', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'Voucher')->get();
            })
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
            Cache::remember('products_pln', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'PLN')->get();
            })
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
            Cache::remember('products_tv', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'TV')->get();
            })
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
            Cache::remember('products_masa_aktif', 1800, function () { // Cache for 30 minutes
                return ProductPrepaid::where('product_category', 'Masa Aktif')->get();
            })
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
        $category = Cache::remember('product_categories', 3600, function () { // Cache for 1 hour
            return ProductPrepaid::select('product_category')
                ->distinct()
                ->orderBy('product_category')
                ->get();
        });

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar kategori produk',
            'data' => [
                'category' => $category
            ]
        ]);
    }

    //get product PDAM pascabayar
    public function pdam()
    {
        $products = $this->applyMarkupToProducts(
            Cache::remember('products_pdam', 1800, function () { // Cache for 30 minutes
                return ProductPasca::where('product_provider', 'PDAM')->get();
            })
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar produk PDAM pascabayar ditemukan',
            'data' => [
                'pdam' => ProductResource::collection($products)
            ]
        ]);
    }

    //get product internet pascabayar
    public function internet()
    {
        $products = $this->applyMarkupToProducts(
            Cache::remember('products_internet', 1800, function () { // Cache for 30 minutes
                return ProductPasca::where('product_provider', 'INTERNET PASCABAYAR')->get();
            })
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar produk internet pascabayar ditemukan',
            'data' => [
                'internet' => ProductResource::collection($products)
            ]
        ]);
    }

    //get product bpjs
    public function bpjs()
    {
        $products = $this->applyMarkupToProducts(
            Cache::remember('products_bpjs', 1800, function () { // Cache for 30 minutes
                return ProductPasca::where('product_provider', 'BPJS KESEHATAN')->get();
            })
        );

        return new ApiResponseResource([
            'status' => 'success',
            'ref_id' => null,
            'message' => 'Daftar produk BPJS ditemukan',
            'data' => [
                'bpjs' => ProductResource::collection($products)
            ]
        ]);
    }
}
