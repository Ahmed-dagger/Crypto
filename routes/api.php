<?php

use App\Http\Controllers\API\AdsController;
use App\Http\Controllers\API\BankingInformationController;
use App\Http\Controllers\API\BinanceController;
use App\Http\Controllers\API\BinanceFuturesController;
use App\Http\Controllers\API\CryptoController;
use App\Http\Controllers\API\CryptoDataController;
use App\Http\Controllers\API\FavouritesController;
use App\Http\Controllers\API\FilterController;
use App\Http\Controllers\API\FutureWalletSimController;
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

    //----------- Google Sign-In Routes -------------//


    Route::post('/google/token-login', [RegisterController::class, 'googleTokenLogin']);

    //----------- Google Sign-In Routes -------------//

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

        Route::post('/put-favourites',  [FavouritesController::class, 'add']);
        Route::get('/users/{user}/favourites', [FavouritesController::class, 'show']);
        Route::delete('/users/{user}/favourites', [FavouritesController::class, 'destroy']);

        Route::post('/buy-crypto', [CryptoController::class, 'buyCrypto']);
        Route::post('/sell-crypto', [CryptoController::class, 'sellCrypto']);
        // Route::post('/user-coins',[CryptoController::class ,'userCoins' ]);
        Route::post('/user-transaction', [CryptoController::class, 'userTransactions']);

        //----------------------P2P------------------------------------------------//
        Route::post('/p2p/create-buy-ad', [P2PController::class, 'createBuyAd']);
        Route::get('/p2p/get-buy-ad', [P2PController::class, 'getBuyAds']);

        Route::post('/p2p/create-sell-ad', [P2PController::class, 'createSellAd']);
        Route::get('/p2p/get-sell-ad', [P2PController::class, 'getSellAds']);

        Route::post('/p2p/edit-buy-ad/{id}', [P2PController::class, 'editBuyAd']);
        Route::delete('/p2p/delete-buy-ad/{id}', [P2PController::class, 'deleteBuyAd']);

        Route::post('/p2p/edit-sell-ad/{id}', [P2PController::class, 'editSellAd']);
        Route::delete('/p2p/delete-sell-ad/{id}', [P2PController::class, 'deleteSellAd']);

        Route::post('/p2p/start/{id}', [P2PController::class, 'startTrade']);
        Route::post('/p2p/complete/{id}', [P2PController::class, 'completeTrade']);
        Route::get('/p2p/user_ads', [P2PController::class, 'getMyAds']);

        Route::post('/p2p/cancel/{id}', [P2PController::class, 'cancelTrade']);


        //----------------------P2P-----------------------------------------------//


        //------------------banking--------------------//

        Route::get('/bank-accounts', [BankingInformationController::class, 'index']); // List all bank accounts
        Route::post('/bank-accounts', [BankingInformationController::class, 'store']); // Create a new bank account
        Route::get('/bank-accounts/{userId}', [BankingInformationController::class, 'show']); // Get details of a specific bank account
        Route::put('/bank-accounts/{id}', [BankingInformationController::class, 'update']); // Update a specific bank account
        Route::delete('/bank-accounts/{id}', [BankingInformationController::class, 'destroy']); // Delete a specific bank account

        //------------------banking--------------------//

        //------------------payment--------------------//
        Route::post('/deposit', [PaymentController::class, 'deposit']);

        Route::post('/paymob/callback', [PaymentController::class, 'paymobCallback']);
        //------------------payment--------------------//


        //-------Binance Futures Routes-----------------//

        Route::post('/futures/order', [BinanceFuturesController::class, 'placeOrder']);
        //Route::get('/futures/position', [BinanceFuturesController::class, 'position']);
        Route::get('/futures/balance', [BinanceFuturesController::class, 'balance']);
        Route::get('/futures/open-orders', [BinanceFuturesController::class, 'openOrders']);
        Route::post('/futures/cancel-order', [BinanceFuturesController::class, 'cancelOrder']);
        Route::get('/futures/order-history', [BinanceFuturesController::class, 'orderHistory']);

        //-------Binance Futures Routes-----------------//

        Route::get('/users/{user}/balance', [CryptoController::class, 'balance']);
        Route::get('/users/{user}/wallets', [CryptoController::class, 'walltes']);
        Route::get('/users/{user}/live-wallet', [CryptoController::class, 'liveWallet']);


        Route::get('/users/{user}/profile', [UserController::class, 'profile']);
        Route::post('/users/{user}/update', [UserController::class, 'update']);


        //----------------------Futures Wallets--------------------//

        // 1. Create a Futures Wallet
        Route::post('/futures/transfer', [FutureWalletSimController::class, 'transferToFutures']);

        // 2. Open a Futures Position
        Route::post('/futures/positions/open', [FutureWalletSimController::class, 'openPosition']);

        // 3. Close an Open Position
        Route::post('/futures/positions/close/{positionId}', [FutureWalletSimController::class, 'closePosition']);

        // 4. Update PnL and Check Liquidation
        Route::post('/futures/positions/update-pnl/{userId}', [FutureWalletSimController::class, 'updatePnL']);


        Route::get('/futures/wallet', [FutureWalletSimController::class, 'getUserFutureWallets']);

        Route::get('/futures/positions', [FutureWalletSimController::class, 'getUserPositions']);

        Route::get('/futures/available-coins', [FutureWalletSimController::class, 'getAvailableFuturesCoinsForUser']);

        Route::post('/futures/transfer-spot', [FutureWalletSimController::class, 'transferToSpot']);

        //----------------------Futures Wallets--------------------//

        // Route::get('/binance/balance', [BinanceController::class, 'balance']);
        // Route::get('/binance/prices', [BinanceController::class, 'prices']);
        // Route::get('/binance/orderbook/{symbol}', [BinanceController::class, 'orderBook']);
        // Route::get('/binance/history/{symbol}', [BinanceController::class, 'priceHistory']);
    });
});
