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

    protected $binanceService;
    
    public function __construct(BinanceService $binanceService)
    {
        $this->binanceService = $binanceService;
    }
    /**
     * Buy cryptocurrency and update user balance
     */

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
        $balance = Wallet::where('user_id' , $user->id )->where('currency' , 'USDT')->first();

        return response()->json([
            "message" => 'success',
            "data" => $balance,
        ]);
     }

     public function walltes(User $user)
     {
        $wallets = Wallet::where('user_id', $user->id)
                     ->where('currency', '!=', 'USDT') // Use '!=' instead of whereNot
                     ->get();

        return response()->json([
            "message" => 'success',
            "data" => $wallets,
        ]);
     }  
     
     public function favourites(Request $request)
     {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string',
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

        $favourite = Favourite::create([
            'user_id' => $user->id,
            'currency' => $currency, 
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saved for favourites successfully',
            'data' => [
                'favourite currency' => $favourite,
            ]
        ], 200);
        
     }

     public function getFavourites(User $user)
     {
        $favourites = Favourite::where('user_id', $user->id)
                     ->get();

        return response()->json([
            "message" => 'success',
            "data" => $favourites,
        ]);
     }
    
}
