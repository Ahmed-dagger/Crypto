<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\BinanceFuturesService;
use App\Http\Controllers\Controller;

class BinanceFuturesController extends Controller
{
    protected $binance;

    public function __construct(BinanceFuturesService $binance)
    {
        $this->binance = $binance;
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:BUY,SELL',
            'quantity' => 'required|numeric',
            'leverage' => 'required|integer|min:1|max:125',
            'reduceOnly' => 'boolean'
        ]);

        $this->binance->setLeverage($validated['symbol'], $validated['leverage']);
        $this->binance->setMarginType($validated['symbol'], 'ISOLATED');

        $response = $this->binance->placeMarketOrder(
            $validated['symbol'],
            $validated['side'],
            $validated['quantity'],
            $validated['reduceOnly'] ?? false
        );

        return response()->json($response);
    }

    public function position()
    {
        return response()->json($this->binance->getPositionInfo());
    }

    public function balance()
    {
        return response()->json($this->binance->getBalance());
    }

    public function openOrders(Request $request)
    {
        $request->validate(['symbol' => 'required|string']);
        return response()->json($this->binance->getOpenOrders($request->symbol));
    }

    public function cancelOrder(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'orderId' => 'required|integer',
        ]);
        return response()->json($this->binance->cancelOrder($validated['symbol'], $validated['orderId']));
    }

    public function orderHistory(Request $request)
    {
        $request->validate(['symbol' => 'required|string']);
        return response()->json($this->binance->getOrderHistory($request->symbol));
    }
}