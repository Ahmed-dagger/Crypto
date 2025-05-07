<?php

use App\Http\Controllers\API\AdsController;
use App\Http\Controllers\API\BinanceController;
use App\Http\Controllers\API\CryptoController;
use App\Http\Controllers\API\CryptoDataController;
use App\Http\Controllers\API\FavouritesController;
use App\Http\Controllers\API\FilterController;
use App\Http\Controllers\API\NewsController;
use App\Http\Controllers\API\P2PController;
use App\Http\Controllers\API\PaymentController;
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

        Route::post('/put-favourites', action: [FavouritesController::class, 'add']);
        Route::get('/users/{user}/favourites', [FavouritesController::class, 'show']);
        Route::delete('/users/{user}/favourites', [FavouritesController::class, 'destroy']);

        Route::post('/buy-crypto', [CryptoController::class, 'buyCrypto']);
        Route::post('/sell-crypto', [CryptoController::class, 'sellCrypto']);
        // Route::post('/user-coins',[CryptoController::class ,'userCoins' ]);
        Route::post('/user-transaction',[CryptoController::class ,'userTransactions' ]);

        //----------------------P2P------------------------------------------------//
        Route::post('/p2p/create-buy-ad',[P2PController::class ,'createBuyAd' ]);
        Route::get('/p2p/get-buy-ad',[P2PController::class ,'getBuyAds' ]);
        Route::post('/p2p/create-sell-ad',[P2PController::class ,'createSellAd' ]);
        Route::get('/p2p/get-sell-ad',[P2PController::class ,'getSellAds' ]);
        Route::post('/p2p/start/{id}',[P2PController::class ,'startTrade' ]);
        Route::post('/p2p/complete/{id}',[P2PController::class ,'completeTrade' ]);
        Route::post('/p2p/cancel/{id}',[P2PController::class ,'cancelTrade' ]);


        //----------------------P2P-----------------------------------------------//


        //------------------payment--------------------//
        Route::post('/deposit', [PaymentController::class, 'deposit']);

        Route::post('/paymob/callback', [PaymentController::class, 'paymobCallback']);
        //------------------payment--------------------//


        Route::get('/users/{user}/balance', [CryptoController::class, 'balance']);
        Route::get('/users/{user}/wallets', [CryptoController::class, 'walltes']);
        
        
        Route::get('/users/{user}/profile', [UserController::class, 'profile']);
        Route::post('/users/{user}/update', [UserController::class , 'update']);

        // Route::get('/binance/balance', [BinanceController::class, 'balance']);
        // Route::get('/binance/prices', [BinanceController::class, 'prices']);
        // Route::get('/binance/orderbook/{symbol}', [BinanceController::class, 'orderBook']);
        // Route::get('/binance/history/{symbol}', [BinanceController::class, 'priceHistory']);
    });
});
