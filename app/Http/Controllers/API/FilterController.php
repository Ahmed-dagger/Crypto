<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CoinGeckoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    protected $coinGeckoService;
    public function __construct(CoinGeckoService $coinGeckoService)
    {
        $this->coinGeckoService = $coinGeckoService;
    }


    public function searchCoins(Request $request)
    {
        $query = strtolower($request->input('query')); // Get search query
        $currency = $request->input('currency', 'usd'); // Default to USD
        $limit = $request->input('limit', 800); // Default limit

        
        $allCoins = $this->coinGeckoService->getAllCoinsData($currency, $limit);

        // Filter coins
        $filteredCoins = $allCoins->filter(function ($coin) use ($query) {
            return str_contains(strtolower($coin['name']), $query) ||
                   str_contains(strtolower($coin['symbol']), $query) ||
                   str_contains(strtolower($coin['id']), $query);

        })->values();
        
        return response()->json($filteredCoins);
    }


    public function getHighestChangeUp()
    {
        return response()->json($this->coinGeckoService->getHighestChangeUp());
    }


    public function getHighestChangeDown()
    {
        return response()->json($this->coinGeckoService->getHighestChangeDown());
    }


    public function getHighestVolume()
    {
        return response()->json($this->coinGeckoService->getHighestVolume());
    }


    public function getNewCoins()
    {
        return response()->json($this->coinGeckoService->getNewCoins());
    }


    public function getPopularCoins()
    {
        return response()->json($this->coinGeckoService->getPopularCoins());
    }
}
