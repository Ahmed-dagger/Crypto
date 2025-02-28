<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CoinGeckoService;
use App\Services\newsService;
use Illuminate\Support\Facades\Auth;

class CryptoDataController extends Controller
{
    protected $coinGeckoService;
    public function __construct(CoinGeckoService $coinGeckoService)
    {
        $this->coinGeckoService = $coinGeckoService;
    }

    public function getAllCoins(Request $request)
    {
        if(!Auth::user())
        {
            return response()->json("unauthticated user" , 401);
        }

        $currency = $request->query('currency', 'usd');  // Default to USD
        $limit = $request->query('limit', 800);
        $data = $this->coinGeckoService->getAllCoinsData($currency, $limit);
        return response()->json($data);
    }

    public function getSingleCoin($symbol, $currency = 'usd')
    {
        $coinData =  $this->coinGeckoService->getSingleCoinData($symbol, $currency);

        return response()->json($coinData);

    }



  
}
