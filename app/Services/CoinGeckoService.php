<?php

namespace App\Services;

use Binance\API;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CoinGeckoService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.coingecko.com/api/v3';
    }

    public function getAllCoinsData($currency = 'usd', $limit = 400)
    {
        $response = Http::get("{$this->baseUrl}/coins/markets", [
            'vs_currency' => $currency,
            'order' => 'market_cap_desc',
            'per_page' => $limit, // Adjust the number of coins returned
            'page' => 1,
            'sparkline' => true,
            'price_change_percentage' => '24h'
        ]);

        if ($response->failed()) {
            return ['error' => 'Failed to fetch data'];
        }

        $data = $response->json();

        return collect($data)->map(function ($coin) {
            return [
                'id' => $coin['id'],
                'name' => $coin['name'],
                'symbol' => strtoupper($coin['symbol']),
                'icon' => $coin['image'],
                'price' => $coin['current_price'],
                'change_rate_percentage' => $coin['price_change_percentage_24h'],
                'change_rate_usdt' => $coin['price_change_24h']
            ];
        });
    }



public function getSingleCoinData($symbol, $currency = 'usd')
{
    // Check if the data is already cached (1 hour cache)
    $cacheKey = "single_coin_data_{$symbol}_{$currency}";
    $coinData = Cache::get($cacheKey);

    if (!$coinData) {
        // Fetch general coin data (e.g., current price, market data)
        $response = Http::get("https://api.coingecko.com/api/v3/coins/{$symbol}", [
            'vs_currency' => $currency
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch coin data'], 500);
        }

        $coin = $response->json();

        // Fetch the detailed chart data for the coin (7d, 30d, 90d)
        $sevenDayData = $this->getCoinChartData($symbol, $currency, 7);
        $thirtyDayData = $this->getCoinChartData($symbol, $currency, 30);
        $ninetyDayData = $this->getCoinChartData($symbol, $currency, 90);

        // Structure the data
        $coinData = [
            'id' => $coin['id'],
            'name' => $coin['name'],
            'symbol' => strtoupper($coin['symbol']),
            'icon' => $coin['image']['large'],
            'price' => $coin['market_data']['current_price'][$currency],
            'change_rate_percentage' => $coin['market_data']['price_change_percentage_24h'],
            'change_rate_usdt' => $coin['market_data']['price_change_24h'],
            'chart_data' => [
                '7d' => $sevenDayData,
                '30d' => $thirtyDayData,
                '90d' => $ninetyDayData
            ]
        ];

        // Cache the data for 1 hour to reduce API calls
        Cache::put($cacheKey, $coinData, 60);
    }

    return response()->json($coinData);
}

// Helper function to fetch chart data for the coin
private function getCoinChartData($coinId, $currency, $days)
{
    // Cache the chart data to reduce the number of requests
    $cacheKey = "chart_data_{$coinId}_{$currency}_{$days}";
    $chartData = Cache::get($cacheKey);

    if (!$chartData) {
        // Fetch chart data for the specified period (7d, 30d, 90d)
        $url = "https://api.coingecko.com/api/v3/coins/{$coinId}/market_chart?vs_currency={$currency}&days={$days}";

        $response = Http::get($url);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();
        
        // Cache the chart data for 1 hour
        $chartData = $data['prices'] ?? [];
        Cache::put($cacheKey, $chartData, 60);
    }

    return $chartData;
}

}
