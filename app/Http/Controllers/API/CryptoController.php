<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BinanceService;
use App\Services\CoinGeckoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CryptoController extends Controller
{



    public function buyCrypto(Request $request, CoinGeckoService $coinGeckoService)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string',
            'amount' => 'required|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in.'
            ], 401);
        }

        $currency = $validated['currency'];
        $amount = $validated['amount'];

        try {
            DB::beginTransaction();

            // ✅ Get crypto price from CoinGecko
            $coinData = $coinGeckoService->getSingleCoinData(strtolower($currency));

            // ✅ Ensure response is an array before logging
            if ($coinData instanceof \Illuminate\Http\JsonResponse) {
                $coinData = $coinData->getData(true);
            }

            Log::info("CoinGecko Response: ", is_array($coinData) ? $coinData : [$coinData]); // Debugging log

            if (!$coinData || !isset($coinData['current_price'])) {
                throw new \Exception("Failed to fetch crypto price for $currency.");
            }

            $price = $coinData['current_price'];
            $totalCost = $price * $amount;

            // ✅ Ensure user has a USD (USDT) wallet
            $fiatWallet = Wallet::where('user_id', $user->id)->where('currency', 'USDT')->first();
            if (!$fiatWallet) {
                throw new \Exception("USD wallet not found.");
            }

            // ✅ Check if user has enough balance
            if ($fiatWallet->balance < $totalCost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient USD balance'
                ], 400);
            }

            // ✅ Create a pending transaction
            $transaction = Transaction::create([
                'wallet_id' => $fiatWallet->id,
                'transaction_type' => 'buy',
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            Log::info("Transaction Created: ", $transaction ? $transaction->toArray() : []); // Debugging log

            if (!$transaction) {
                throw new \Exception("Failed to create transaction record.");
            }

            // ✅ Deduct balance from USD wallet
            $fiatWallet->balance -= $totalCost;
            $fiatWallet->save();

            // ✅ Get or create crypto wallet
            $cryptoWallet = Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency' => $currency],
                ['balance' => 0]
            );

            // ✅ Add crypto to user's wallet
            $cryptoWallet->balance += $amount;
            $cryptoWallet->save();

            // ✅ Mark transaction as completed
            $transaction->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase successful',
                'data' => [
                    'currency' => $currency,
                    'amount' => $amount,
                    'price' => $price,
                    'total_cost' => $totalCost,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Buy Crypto Error: " . $e->getMessage()); // Log error

            return response()->json([
                'success' => false,
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sellCrypto(Request $request, CoinGeckoService $coinGeckoService)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string',
            'amount' => 'required|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in.'
            ], 401);
        }

        $currency = strtoupper($validated['currency']);
        $amount = $validated['amount'];

        try {
            DB::beginTransaction();

            // ✅ Get crypto price from CoinGecko
            $coinData = $coinGeckoService->getSingleCoinData(strtolower($currency));

            // ✅ Ensure response is an array before logging
            if ($coinData instanceof \Illuminate\Http\JsonResponse) {
                $coinData = $coinData->getData(true);
            }

            Log::info("CoinGecko Response: ", is_array($coinData) ? $coinData : [$coinData]); // Debugging log

            if (!$coinData || !isset($coinData['current_price'])) {
                throw new \Exception("Failed to fetch crypto price for $currency.");
            }

            $price = $coinData['current_price'];
            $totalCost = $price * $amount;

            // ✅ Ensure user has a crypto wallet for the coin they want to sell
            $cryptoWallet = Wallet::where('user_id', $user->id)->where('currency', $currency)->first();
            if (!$cryptoWallet) {
                throw new \Exception("Crypto wallet for $currency not found.");
            }

            // ✅ Check if user has enough crypto balance
            if ($cryptoWallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient crypto balance'
                ], 400);
            }

            // ✅ Create a pending transaction
            $transaction = Transaction::create([
                'wallet_id' => $cryptoWallet->id,
                'transaction_type' => 'sell',
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            Log::info("Transaction Created: ", $transaction ? $transaction->toArray() : []); // Debugging log

            if (!$transaction) {
                throw new \Exception("Failed to create transaction record.");
            }

            // ✅ Deduct balance from crypto wallet
            $cryptoWallet->balance -= $amount;

            // ✅ Delete wallet if balance reaches 0

            $cryptoWallet->save();

            // ✅ Get or create USD (USDT) wallet
            $fiatWallet = Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency' => 'USDT'],
                ['balance' => 0]
            );

            // ✅ Add equivalent USD value to user's wallet
            $fiatWallet->balance += $totalCost;
            $fiatWallet->save();

            // ✅ Mark transaction as completed
            $transaction->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sell successful',
                'data' => [
                    'currency' => $currency,
                    'amount' => $amount,
                    'price' => $price,
                    'total_cost' => $totalCost,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sell Crypto Error: " . $e->getMessage()); // Log error

            return response()->json([
                'success' => false,
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function balance(User $user)
    {
        $balance = Wallet::where('user_id', $user->id)->where('currency', 'USDT')->first();

        return response()->json([
            "message" => 'success',
            "data" => $balance,
        ]);
    }

    public function walltes(User $user, CoinGeckoService $coinGeckoService)
    {
        // Get all wallet entries for the user
        $wallets = Wallet::where('user_id', $user->id)->get();

        if ($wallets->isEmpty()) {
            return response()->json([
                'message' => 'No favourite coins found.',
                'data' => [],
            ], 200);
        }

        // Fetch all coins data from the service
        $allCoins = $coinGeckoService->getAllCoinsData();

        // Filter and map the coins with balance
        $filteredCoins = $wallets->map(function ($wallet) use ($allCoins) {
            $coin = $allCoins->firstWhere('id', $wallet->currency);

            if ($coin) {
                $coin['balance'] = $wallet->balance;
                return $coin;
            }

            return null;
        })->filter()->values(); // Remove nulls and reindex

        $numberOfCoins = $filteredCoins->count();

        return response()->json([
            'message' => 'Success',
            'data' => [
                'coins of the user' => $filteredCoins,
                'number of coins the user purchased' => $numberOfCoins
            ],
        ], 200);
    }


    public function userCoins(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer', // use 'integer' instead of 'number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Fetch wallet data
        $wallet = Wallet::where('user_id', $validated['user_id'])
            ->where('currency', '!=', 'USDT')
            ->get();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found for this user'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet data retrieved successfully',
            'data' => $wallet
        ]);
    }

    public function userTransactions(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Fetch all wallets for the user
        $wallets = Wallet::where('user_id', $validated['user_id'])->get();

        if ($wallets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No wallets found for this user'
            ], 404);
        }

        // Fetch all transactions related to the user's wallets
        $transactions = Transaction::whereIn('wallet_id', $wallets->pluck('id'))->get();

        return response()->json([
            'success' => true,
            'message' => 'Transactions retrieved successfully',
            'data' => $transactions

        ]);
    }

public function liveWallet(Request $request, CoinGeckoService $coinGeckoService, User $user)
{
    // Get all user wallets
    $wallets = Wallet::where('user_id', $user->id)->get();

    if ($wallets->isEmpty()) {
        return response()->json([
            'message' => 'No coins found in wallet.',
            'data' => [],
        ], 200);
    }

    // Get USDT wallet
    $usdtWallet = $wallets->firstWhere('currency', 'tether') ?? $wallets->firstWhere('currency', 'USDT');
    $usdtBalance = $usdtWallet ? $usdtWallet->balance : 0;

    // Extract coin IDs
    $coinIds = $wallets->pluck('currency')->toArray();

    // Get live prices from CoinGecko
    $livePrices = $coinGeckoService->getLivePrices($coinIds);

    // Map wallet data
    $walletData = $wallets->map(function ($wallet) use ($livePrices) {
        $coinId = $wallet->currency;

        if (!isset($livePrices[$coinId])) {
            return null;
        }

        $priceData = $livePrices[$coinId];
        $currentPrice = $priceData['usd'];
        $priceChange24h = $priceData['usd_24h_change'];

        $balance = $wallet->balance;
        $avgBuyPrice = $wallet->avg_buy_price ?? 0;

        $currentValue = $balance * $currentPrice;
        $initialValue = $balance * $avgBuyPrice;
        $profitLoss = $currentValue - $initialValue;

        return [
            'coin' => $coinId,
            'balance' => $balance,
            'current_price' => $currentPrice,
            'value_usd' => round($currentValue, 2),
            '24h_change_percent' => round($priceChange24h, 2),
            'profit_loss_usd' => round($profitLoss, 2),
        ];
    })->filter()->values();

    // If USDT exists, push it
    if ($usdtWallet) {
        $walletData->push([
            'coin' => 'tether',
            'balance' => $usdtBalance,
            'current_price' => 1.00,
            'value_usd' => round($usdtBalance, 2),
            '24h_change_percent' => 0,
            'profit_loss_usd' => 0,
        ]);
    }

    $totalBalance = $walletData->sum('value_usd');
    $totalProfitLoss = $walletData->sum('profit_loss_usd');
    $totalProfitLossPercentage = ($totalBalance > 0)
        ? ($totalProfitLoss > 0 ? round(($totalProfitLoss / $totalBalance) * 100, 2) : 0)
        : 0;

    return response()->json([
        'message' => 'Live wallet summary',
        'data' => [
            'wallets' => $walletData,
            'total_balance_usd' => round($totalBalance, 2),
            'total_profit_loss_usd' => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => $totalProfitLossPercentage,
            'usdt_wallet_balance' => round($usdtBalance, 2), // <- added field
        ]
    ]);
}

}
