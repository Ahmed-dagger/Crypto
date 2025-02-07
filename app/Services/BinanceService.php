<?php

namespace App\Services;

use Binance\API;
use Illuminate\Support\Facades\Http;

class BinanceService
{
    protected $api;

    public function __construct()
    {
        $this->api = new API(config('services.binance.key'), config('services.binance.secret'));

        $this->api->useServerTime();
    }

    // Fetch account balance
    public function getBalance()
    {
        return $this->api->balances();
    }

    // Fetch market prices
    public function getPrices()
    {
        return $this->api->prices();
    }

    // Fetch order book for a specific symbol
    public function getOrderBook($symbol = 'BTCUSDT')
    {
        return $this->api->depth($symbol);
    }

    // Place a test order
    public function placeTestOrder($symbol, $quantity, $price)
    {
        return $this->api->buy($symbol, $quantity, $price, "LIMIT", ["timeInForce" => "GTC"]);
    }

    public function getPriceHistory($symbol, $interval = '1m', $limit = 30)
    {
        $apiUrl = "https://api.binance.com/api/v3/klines?symbol={$symbol}&interval={$interval}&limit={$limit}";

        // Make API Request
        $response = Http::get($apiUrl);

        if ($response->failed()) {
            return ['error' => 'Failed to fetch data from Binance'];
        }

        $data = $response->json();

        // Format response for chart
        $chartData = [];
        foreach ($data as $item) {
            $chartData[] = [
                'timestamp' => date('H:i:s', $item[0] / 1000), // Convert to human-readable time
                'open' => $item[1],   // Open price
                'high' => $item[2],   // Highest price
                'low' => $item[3],    // Lowest price
                'close' => $item[4],  // Closing price
                'volume' => $item[5]  // Volume traded
            ];
        }

        return $chartData;
    }
}
