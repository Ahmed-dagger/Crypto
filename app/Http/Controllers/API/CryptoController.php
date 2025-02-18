<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\BinanceService;
use Illuminate\Support\Facades\DB;

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

    public function buyCrypto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string', // e.g., BTC, ETH
            'amount' => 'required|numeric|min:0.0001', // Amount of crypto to buy
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $validated = $validator->validated();
        $user = auth()->user(); // Ensure the user is authenticated
    
        $currency = strtoupper($validated['currency']);
        $amount = $validated['amount'];
    
        try {
            // Begin Database Transaction (Ensures all-or-nothing)
            DB::beginTransaction();
    
            // Get real-time price from Binance
            $price = $this->getCryptoPrice($currency);
            if (!$price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid currency or failed to fetch price'
                ], 400);
            }
    
            // Calculate total cost in USD
            $totalCost = $price * $amount;
    
            // Check user's USD balance
            $fiatWallet = Wallet::where('user_id', $user->id)->where('currency', 'USD')->first();
            if (!$fiatWallet || $fiatWallet->balance < $totalCost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient USD balance'
                ], 400);
            }
    
            // Create a "pending" transaction record first
            $transaction = Transaction::create([
                'wallet_id' => $fiatWallet->id,
                'transaction_type' => 'buy',
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending', // Mark transaction as pending initially
            ]);
    
            //  Deduct USD balance
            $fiatWallet->balance -= $totalCost;
            $fiatWallet->save();
    
            //  Add purchased crypto to user's wallet
            $cryptoWallet = Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency' => $currency],
                ['balance' => 0]
            );
            $cryptoWallet->balance += $amount;
            $cryptoWallet->save();
    
            //  Update transaction status to "completed"
            $transaction->update(['status' => 'completed']);
    
            // Commit the transaction (all changes are saved)
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
            // Rollback transaction (undo changes if any step fails)
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * Fetch the real-time price of a cryptocurrency from Binance API
     */
    private function getCryptoPrice($symbol)
    {        return response()->json($this->binanceService->getPrices());

    }
}
