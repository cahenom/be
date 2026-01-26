<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentRequestCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => true,
            'message' => 'Payment requests retrieved successfully',
            'data' => [
                'payment_requests' => $this->collection
            ],
            'code' => 200
        ];
    }
}
