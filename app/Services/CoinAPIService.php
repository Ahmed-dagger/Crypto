<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CoinAPIService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://rest.coinapi.io/v1';
        $this->apiKey = config('services.coinapi.key');
    }

    public function getCryptoPrices()
    {
        $response = Http::withHeaders([
            'X-CoinAPI-Key' => $this->apiKey // Sending API Key in Headers
        ])->get("{$this->baseUrl}/assets");

        if ($response->failed()) {
            return [
                'error' => 'Failed to fetch data from CoinAPI',
                'status' => $response->status(),
                'message' => $response->json()
            ];
        }

        return $response->json();
    }
}
