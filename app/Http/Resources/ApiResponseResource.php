<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'status'  => $this->resource['status'],
            'message' => $this->resource['message'],
            'data'    => $this->resource['data'] ?? null,
        ];
    }
}
