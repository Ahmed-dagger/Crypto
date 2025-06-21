<?php

namespace App\Services;

use Binance\API;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CoinGeckoService
{
    protected $baseUrl;
    protected $newsUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.coingecko.com/api/v3';
    }

    public function getAllCoinsData($currency = 'usd', $limit = 800)
    {
        $cacheKey = "all_coins_data_{$currency}_{$limit}";
        $data = Cache::get($cacheKey);

        if (!$data) {
            $allCoins = collect(); // Store all coins here
            $perPage = 250; // Max limit per page
            $pages = ceil($limit / $perPage); // Number of pages to fetch

            for ($page = 1; $page <= $pages; $page++) {
                $response = Http::get("{$this->baseUrl}/coins/markets", [
                    'vs_currency' => $currency,
                    'order' => 'market_cap_desc',
                    'per_page' => $perPage,
                    'page' => $page,
                    'sparkline' => false,
                    'price_change_percentage' => '24h'
                ]);

                if ($response->failed()) {
                    return collect(Cache::get($cacheKey, [])); // Return cached data if available
                }

                $coins = collect($response->json());
                $allCoins = $allCoins->merge($coins);

                if ($allCoins->count() >= $limit) {
                    break;
                }

                sleep(1); // Reduce delay for better performance
            }

            $data = $allCoins->take($limit)->map(fn($coin) => $this->formatCoinData($coin));
            Cache::put($cacheKey, $data, 3600); // Cache for 1 hour
        }

        return collect($data);
    }

    private function formatCoinData($coin): array
    {
        return [
            'id' => $coin['id'],
            'name' => $coin['name'],
            'symbol' => strtoupper($coin['symbol']),
            'icon' => $coin['image'],
            'price' => $coin['current_price'],
            'change_rate_percentage' => $coin['price_change_percentage_24h'] ?? 0,
            'change_rate_usdt' => $coin['price_change_24h'] ?? 0,
            'volume' => $coin['total_volume'],
            'market_cap' => $coin['market_cap'],
            'market_cap_rank' => $coin['market_cap_rank'],
        ];
    }

    // 🔹 Get top 10 coins with the highest % increase in 24h
    public function getHighestChangeUp()
    {
        return $this->getAllCoinsData()
            ->filter(fn($coin) => isset($coin['change_rate_percentage']))
            ->sortByDesc('change_rate_percentage')
            ->take(10);
    }

    // 🔹 Get top 10 coins with the highest % decrease in 24h
    public function getHighestChangeDown()
    {
        return $this->getAllCoinsData()
            ->filter(fn($coin) => isset($coin['change_rate_percentage']))
            ->sortBy('change_rate_percentage')
            ->take(10);
    }

    // 🔹 Get top 10 coins with the highest trading volume
    public function getHighestVolume()
    {
        return $this->getAllCoinsData()
            ->sortByDesc('volume')
            ->take(10);
    }

    // 🔹 Get new coins (Rank ≥ 500)
    public function getNewCoins()
    {
        return $this->getAllCoinsData()
            ->filter(fn($coin) => $coin['market_cap_rank'] >= 500)
            ->take(10);
    }

    // 🔹 Get most popular coins (Top 10 by Market Cap Rank)
    public function getPopularCoins()
    {
        return $this->getAllCoinsData()
            ->sortBy('market_cap_rank')
            ->take(10);
    }


    public function getSingleCoinData($symbol, $currency = 'usd')
    {
        // Cache key to store the fetched data for 1 hour
        $cacheKey = "single_coin_data_{$symbol}_{$currency}";
        $coinData = Cache::get($cacheKey);

        if (!$coinData) {
            // Fetch general coin data
            $response = Http::get("https://api.coingecko.com/api/v3/coins/{$symbol}");

            if ($response->failed()) {

                $cachedData = Cache::get($cacheKey);
                if ($cachedData) {
                    return $cachedData;
                }
                return response()->json(['error' => 'Failed to fetch coin data'], 500);
            }

            $coin = $response->json();

            // Fetch historical chart data
            $sevenDayData = $this->getCoinChartData($symbol, $currency, 7);
            $thirtyDayData = $this->getCoinChartData($symbol, $currency, 30);
            $ninetyDayData = $this->getCoinChartData($symbol, $currency, 90);

            // Format response
            $coinData = [
                "id" => $coin["id"],
                "symbol" => strtoupper($coin["symbol"]),
                "name" => $coin["name"],
                "image" => $coin["image"]["large"],
                "current_price" => $coin["market_data"]["current_price"][$currency],
                "market_cap" => $coin["market_data"]["market_cap"][$currency],
                "market_cap_rank" => $coin["market_cap_rank"],
                "fully_diluted_valuation" => $coin["market_data"]["fully_diluted_valuation"][$currency],
                "total_volume" => $coin["market_data"]["total_volume"][$currency],
                "high_24h" => $coin["market_data"]["high_24h"][$currency],
                "low_24h" => $coin["market_data"]["low_24h"][$currency],
                "price_change_24h" => $coin["market_data"]["price_change_24h"],
                "price_change_percentage_24h" => $coin["market_data"]["price_change_percentage_24h"],
                "market_cap_change_24h" => $coin["market_data"]["market_cap_change_24h"],
                "market_cap_change_percentage_24h" => $coin["market_data"]["market_cap_change_percentage_24h"],
                "circulating_supply" => $coin["market_data"]["circulating_supply"],
                "total_supply" => $coin["market_data"]["total_supply"],
                "max_supply" => $coin["market_data"]["max_supply"],
                "ath" => $coin["market_data"]["ath"][$currency],
                "ath_change_percentage" => $coin["market_data"]["ath_change_percentage"][$currency],
                "ath_date" => $coin["market_data"]["ath_date"],
                "atl" => $coin["market_data"]["atl"][$currency],
                "atl_change_percentage" => $coin["market_data"]["atl_change_percentage"][$currency],
                "atl_date" => $coin["market_data"]["atl_date"],
                "last_updated" => $coin["last_updated"],
                "chart_data" => [
                    "7d" => $sevenDayData,
                    "30d" => $thirtyDayData,
                    "90d" => $ninetyDayData
                ]
            ];

            // Cache the data for 1 hour
            Cache::put($cacheKey, $coinData, 60);
        }

        return response()->json($coinData);
    }

    // Helper function to fetch chart data for the coin
    private function getCoinChartData($coinId, $currency, $days)
    {
        $cacheKey = "chart_data_{$coinId}_{$currency}_{$days}";
        $chartData = Cache::get($cacheKey);

        if (!$chartData) {
            $url = "https://api.coingecko.com/api/v3/coins/{$coinId}/market_chart";
            $response = Http::get($url, [
                'vs_currency' => $currency,
                'days' => $days
            ]);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();

            // Format the chart data to match required structure
            $chartData = array_map(function ($price) {
                return [
                    'timestamp' => $price[0], // Timestamp
                    'price' => $price[1] // Price
                ];
            }, $data['prices'] ?? []);

            // Cache the chart data for 1 hour
            Cache::put($cacheKey, $chartData, 60);
        }

        return $chartData;
    }

    public function getLivePrices(array $coinIds)
    {
        $ids = implode(',', $coinIds);
        $url = "{$this->baseUrl}/simple/price";

        $response = Http::get($url, [
            'ids' => $ids,
            'vs_currencies' => 'usd',
            'include_24hr_change' => 'true',
        ]);

        if ($response->successful()) {
            return $response->json(); // Example: ['bitcoin' => ['usd' => 62000, 'usd_24h_change' => 1.23], ...]
        }

        return [];
    }
}
