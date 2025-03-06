<?php

use App\Http\Controllers\API\AdsController;
use App\Http\Controllers\API\BinanceController;
use App\Http\Controllers\API\CryptoController;
use App\Http\Controllers\API\CryptoDataController;
use App\Http\Controllers\API\FilterController;
use App\Http\Controllers\API\NewsController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['name' => 'App\Http\Controllers\Api'], function () {

    Route::apiResource('users', UserController::class);

    Route::post('/register', [RegisterController::class, 'register']);  // Step 2 - Verify OTP
    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp']);  // Step 2 - Verify OTP
    Route::post('/set-password', [RegisterController::class, 'setPassword']);
    Route::post('/forget-password', [ResetPasswordController::class, 'sendResetOtp']);
    Route::post('/verify-password-otp', [ResetPasswordController::class, 'verifyResetPasswordOtp']);
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
    Route::post('auth', [UserController::class, 'auth']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/crypto/news', [NewsController::class, 'getNews']);
        Route::get('/crypto/ads', [AdsController::class, 'Ads']);
        Route::get('/crypto/search', [FilterController::class, 'searchCoins']);
        Route::get('/crypto//filter/highest-change-up', [FilterController::class, 'getHighestChangeUp']);
        Route::get('/crypto/filter/highest-change-down', [FilterController::class, 'getHighestChangeDown']);
        Route::get('/crypto/filter/highest-volume', [FilterController::class, 'getHighestVolume']);
        Route::get('/crypto/filter/new-coins', [FilterController::class, 'getNewCoins']);
        Route::get('/crypto/filter/popular-coins', [FilterController::class, 'getPopularCoins']);
       
    });

    //Route::get('/crypto-prices', [CryptoController::class, 'getCryptoPrices']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/crypto/all', [CryptoDataController::class, 'getAllCoins']);
        Route::get('/crypto/{symbol}', [CryptoDataController::class, 'getSingleCoin']);

        Route::post('/buy-crypto', [CryptoController::class, 'buyCrypto']);
        Route::post('/sell-crypto', [CryptoController::class, 'sellCrypto']);
        Route::post('/put-favourites', [CryptoController::class, 'favourites']);
        Route::get('/users/{user}/balance', [CryptoController::class, 'balance']);
        Route::get('/users/{user}/wallets', [CryptoController::class, 'walltes']);
        Route::get('/users/{user}/favourites', [CryptoController::class, 'getFavourites']);
        
        Route::get('/users/{user}/profile', [UserController::class, 'profile']);

        // Route::get('/binance/balance', [BinanceController::class, 'balance']);
        // Route::get('/binance/prices', [BinanceController::class, 'prices']);
        // Route::get('/binance/orderbook/{symbol}', [BinanceController::class, 'orderBook']);
        // Route::get('/binance/history/{symbol}', [BinanceController::class, 'priceHistory']);
    });
});
