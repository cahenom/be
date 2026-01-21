<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponseResource extends JsonResource
{
    protected $httpStatusCode = 200;

    public function __construct($resource, $httpStatusCode = 200)
    {
        parent::__construct($resource);
        $this->httpStatusCode = $httpStatusCode;
    }

    public function toArray($request)
    {
        // If resource is an array with status code as second element, extract the actual data
        if (is_array($this->resource) && isset($this->resource[0]) && !isset($this->resource['status'])) {
            // This means the resource was passed as [data, statusCode] format
            return [
                'status'  => $this->resource[0]['status'],
                'message' => $this->resource[0]['message'],
                'data'    => $this->resource[0]['data'] ?? null,
                'code'    => $this->httpStatusCode,
            ];
        }

        // Normal case where resource is just the data array
        return [
            'status'  => $this->resource['status'],
            'message' => $this->resource['message'],
            'data'    => $this->resource['data'] ?? null,
            'code'    => $this->httpStatusCode,
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->httpStatusCode);
    }
}
