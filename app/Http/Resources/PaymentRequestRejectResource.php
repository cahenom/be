<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestRejectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => true,
            'message' => 'Payment request rejected successfully',
            'data' => [
                'payment_request' => $this->resource['payment_request'] ?? null,
                'transaction' => $this->resource['transaction'] ?? null
            ],
            'code' => 200
        ];
    }
}
