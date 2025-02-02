<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CoinAPIService;

class CryptoController extends Controller
{
    protected $coinAPIService;

    public function __construct(CoinAPIService $coinAPIService)
    {
        $this->coinAPIService = $coinAPIService;
    }

    public function getCryptoPrices()
    {
        return response()->json($this->coinAPIService->getCryptoPrices());
    }
}
