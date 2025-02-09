<?php

use App\Http\Controllers\API\BinanceController;
use App\Http\Controllers\API\CryptoController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['name' => 'App\Http\Controllers\Api' ] , function() {

    Route::apiResource('users' , UserController::class);

    Route::post('/register', [RegisterController::class, 'register']);  // Step 2 - Verify OTP
    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp']);  // Step 2 - Verify OTP
    Route::post('/set-password', [RegisterController::class, 'setPassword']); 

    Route::post('auth',[UserController::class , 'auth']);
    
    //Route::get('/crypto-prices', [CryptoController::class, 'getCryptoPrices']);

    Route::get('/binance/balance', [BinanceController::class, 'balance']);
    Route::get('/binance/prices', [BinanceController::class, 'prices']);
    Route::get('/binance/orderbook/{symbol}', [BinanceController::class, 'orderBook']);
    Route::get('/binance/history/{symbol}', [BinanceController::class, 'priceHistory']);

});

