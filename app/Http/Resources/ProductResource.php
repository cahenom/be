<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id"        => $this->id,
            "name"      => $this->product_name,
            "desc"      => $this->product_desc,
            "category"  => $this->product_category,
            "provider"  => $this->product_provider,
            "type"      => $this->product_type,
            "sku"       => $this->product_sku,
            "multi"     => $this->product_multi,
            "price"     => $this->product_buyer_price,  // hanya harga jual
            "updated_at"=> $this->updated_at,
        ];
    }
}
