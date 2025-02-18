<?php

use App\Http\Controllers\API\BinanceController;
use App\Http\Controllers\API\CryptoController;
use App\Http\Controllers\API\CryptoDataController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['name' => 'App\Http\Controllers\Api'], function () {

    Route::apiResource('users', UserController::class);

    Route::post('/register', [RegisterController::class, 'register']);  // Step 2 - Verify OTP
    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp']);  // Step 2 - Verify OTP
    Route::post('/set-password', [RegisterController::class, 'setPassword']);
    Route::post('/forget-password', [ResetPasswordController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
    


    Route::post('auth', [UserController::class, 'auth']);

    //Route::get('/crypto-prices', [CryptoController::class, 'getCryptoPrices']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/crypto/all' , [CryptoDataController::class , 'getAllCoins']);
        Route::get('/crypto/{symbol}' , [CryptoDataController::class , 'getSingleCoin']);
        // Route::post('/buy-crypto', [CryptoController::class, 'buyCrypto']);
        // Route::get('/binance/balance', [BinanceController::class, 'balance']);
        // Route::get('/binance/prices', [BinanceController::class, 'prices']);
        // Route::get('/binance/orderbook/{symbol}', [BinanceController::class, 'orderBook']);
        // Route::get('/binance/history/{symbol}', [BinanceController::class, 'priceHistory']);
    });
});
