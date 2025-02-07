<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BinanceService;

class BinanceController extends Controller
{
    protected $binanceService;

    public function __construct(BinanceService $binanceService)
    {
        $this->binanceService = $binanceService;
    }

    public function balance()
    {
        return response()->json($this->binanceService->getBalance());
    }

    public function prices()
    {
        return response()->json($this->binanceService->getPrices());
    }

    public function orderBook($symbol)
    {
        return response()->json($this->binanceService->getOrderBook($symbol));
    }

    public function priceHistory(Request $request, $symbol)
    {
        $interval = $request->query('interval', '1m');   // Default: 1 minute
        $limit = $request->query('limit', 30);          // Default: 30 entries

        $chartData = $this->binanceService->getPriceHistory($symbol, $interval, $limit);

        return response()->json($chartData);
    }
}