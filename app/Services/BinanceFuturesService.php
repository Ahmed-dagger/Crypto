<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BinanceFuturesService
{
    protected $apiKey;
    protected $secret;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.binance.api_key');
        $this->secret = config('services.binance.secret_key');
        $this->baseUrl = config('services.binance.base_url');
    }

    public function getServerTime()
    {
        $response = Http::get("{$this->baseUrl}/fapi/v1/time");
        return $response->json()['serverTime'] ?? round(microtime(true) * 1000);
    }

    protected function getTimestamp(): int
    {
        static $offset = null;

        if ($offset === null) {
            $serverTime = $this->getServerTime();
            $localTime = round(microtime(true) * 1000);
            $offset = $serverTime - $localTime;
        }

        return round(microtime(true) * 1000) + $offset;
    }



    protected function sign(array $params): array
    {
        $params['timestamp'] = $this->getTimestamp();
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $params['signature'] = hash_hmac('sha256', $query, $this->secret);
        return $params;
    }



    public function placeMarketOrder($symbol, $side, $quantity, $reduceOnly = false)
    {
        $params = [
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'MARKET',
            'quantity' => $quantity,
            'reduceOnly' => $reduceOnly ? 'true' : 'false',
        ];

        // Add timestamp + signature
        $signedParams = $this->sign($params);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])
            ->asForm() // 🔥 Critical: send as form-encoded
            ->post("{$this->baseUrl}/fapi/v1/order", $signedParams)
            ->json();
    }


    public function setLeverage($symbol, $leverage)
    {
        $params = $this->sign([
            'symbol' => $symbol,
            'leverage' => $leverage
        ]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->post("{$this->baseUrl}/fapi/v1/leverage", $params)->json();
    }

    public function setMarginType($symbol, $marginType = 'ISOLATED')
    {
        $params = $this->sign([
            'symbol' => $symbol,
            'marginType' => $marginType
        ]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->post("{$this->baseUrl}/fapi/v1/marginType", $params)->json();
    }

    public function getPositionInfo()
    {
        $params = $this->sign([]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get("{$this->baseUrl}/fapi/v2/positionRisk", $params)->json();
    }

    public function getBalance()
    {
        $params = $this->sign([]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get("{$this->baseUrl}/fapi/v2/balance", $params)->json();
    }

    public function getOpenOrders($symbol)
    {
        $params = $this->sign(['symbol' => $symbol]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get("{$this->baseUrl}/fapi/v1/openOrders", $params)->json();
    }

    public function cancelOrder($symbol, $orderId)
    {
        $params = $this->sign([
            'symbol' => $symbol,
            'orderId' => $orderId
        ]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->delete("{$this->baseUrl}/fapi/v1/order", ['query' => $params])->json();
    }

    public function getOrderHistory($symbol)
    {
        $params = $this->sign(['symbol' => $symbol]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get("{$this->baseUrl}/fapi/v1/allOrders", $params)->json();
    }
}
