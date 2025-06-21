<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Wallet;
use App\Services\CoinGeckoService;
use Illuminate\Http\Request;
use App\Models\FutureWallet;
use Illuminate\Support\Facades\DB;

class FutureWalletSimController extends Controller
{
    protected $coinGecko;

    public function __construct(CoinGeckoService $coinGecko)
    {
        $this->coinGecko = $coinGecko;
    }
    public function transferToFutures(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency' => 'required|string',
            'amount' => 'required|numeric|min:0.0001',
        ]);

        $userId = $validated['user_id'];
        $currency = strtoupper($validated['currency']);
        $amount = $validated['amount'];

        $spotWallet = Wallet::where('user_id', $userId)->where('currency', $currency)->first();
        $futuresWallet = FutureWallet::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency],
            ['balance' => 0, 'margin' => 0]
        );

        if (!$spotWallet || $spotWallet->balance < $amount) {
            return response()->json(['message' => 'Insufficient spot balance'], 400);
        }

        $spotWallet->balance -= $amount;
        $spotWallet->save();

        $futuresWallet->balance += $amount;
        $futuresWallet->save();

        return response()->json(['message' => 'Transfer successful']);
    }


    public function transferToSpot(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency' => 'required|string',
            'amount' => 'required|numeric|min:0.0001',
        ]);

        $userId = $validated['user_id'];
        $currency = strtoupper($validated['currency']);
        $amount = $validated['amount'];

        $futuresWallet = FutureWallet::where('user_id', $userId)->where('currency', $currency)->first();
        $spotWallet = Wallet::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency],
            ['balance' => 0]
        );

        if (!$futuresWallet || $futuresWallet->balance < $amount) {
            return response()->json(['message' => 'Insufficient futures balance'], 400);
        }

        // ✅ Wrap in DB transaction to ensure atomicity
        DB::transaction(function () use ($futuresWallet, $spotWallet, $amount) {
            $futuresWallet->balance -= $amount;
            $futuresWallet->save();

            $spotWallet->balance += $amount;
            $spotWallet->save();
        });

        return response()->json(['message' => 'Transfer to spot successful']);
    }



    /**
     * Open a futures position.
     */
    public function openPosition(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'currency' => 'required|string',
            'size' => 'required|numeric|min:0.0001',
            'leverage' => 'required|numeric|min:1|max:100',
            'direction' => 'required|in:long,short',
        ]);

        $currency = strtoupper($validated['currency']);
        $allowedCoins = ['BTC', 'ETH', 'SOL', 'BNB', 'ADA', 'XRP'];

        if (!in_array($currency, $allowedCoins)) {
            return response()->json(['message' => 'This asset is not supported for futures trading'], 403);
        }

        $wallet = FutureWallet::where('user_id', $user->id)->where('currency', $currency)->first();
        if (!$wallet) {
            return response()->json(['message' => 'Futures wallet not found'], 404);
        }

        $price = $this->getCurrentPrice($currency);
        if (!$price) {
            return response()->json(['message' => 'Failed to fetch current price'], 500);
        }

        $size = $validated['size'];
        $leverage = $validated['leverage'];
        $direction = $validated['direction'];
        $marginRequired = $size / $leverage;

        if ($wallet->balance < $marginRequired) {
            return response()->json(['message' => 'Insufficient margin'], 400);
        }

        $wallet->balance -= $marginRequired;
        $wallet->margin += $marginRequired;
        $wallet->save();

        Position::create([
            'user_id' => $user->id,
            'futures_wallet_id' => $wallet->id,
            'currency' => $currency,
            'direction' => $direction,
            'entry_price' => $price,
            'size' => $size,
            'leverage' => $leverage,
            'margin' => $marginRequired,
        ]);

        return response()->json(['message' => 'Position opened successfully']);
    }

    /**
     * Update all open positions' PnL for a user.
     */
    public function updatePnL($userId)
    {
        $positions = Position::where('user_id', $userId)->where('is_open', true)->get();

        $results = [];

        foreach ($positions as $position) {
            $price = $this->getCurrentPrice($position->currency);
            if (!$price) continue;

            $entry = $position->entry_price;
            $size = $position->size;

            $pnl = $position->direction === 'long'
                ? ($price - $entry) * $size
                : ($entry - $price) * $size;

            $position->unrealized_pnl = $pnl;
            $position->save();

            $wallet = $position->wallet;
            $equity = $wallet->margin + $pnl;

            $status = 'open';

            if ($equity <= $position->margin * 0.5) {
                $position->is_open = false;
                $wallet->margin -= $position->margin;
                $wallet->save();
                $position->save();

                $status = 'closed (liquidated)';
            }

            $results[] = [
                'position_id' => $position->id,
                'currency' => $position->currency,
                'entry_price' => $entry,
                'current_price' => $price,
                'unrealized_pnl' => $pnl,
                'status' => $status,
                'direction' => $position->direction,
            ];
        }

        return response()->json([
            'message' => 'PnL updated',
            'positions' => $results
        ]);
    }


    /**
     * Close an open position.
     */
    public function closePosition($positionId)
    {
        $position = Position::where('id', $positionId)
            ->where('id', $positionId)
            ->where('user_id', auth()->id())
            ->where('is_open', true)
            ->first();

        if (!$position) {
            return response()->json(['message' => 'Position not found'], 404);
        }

        $price = $this->getCurrentPrice($position->currency);
        if (!$price) {
            return response()->json(['message' => 'Failed to fetch price'], 500);
        }

        if (!$position || !$position->wallet) {
            return response()->json(['message' => 'Position or wallet not found'], 404);
        }


        $pnl = $position->direction === 'long'
            ? ($price - $position->entry_price) * $position->size
            : ($position->entry_price - $price) * $position->size;

        $wallet = $position->wallet;
        $wallet->margin -= $position->margin;
        $wallet->balance += $position->margin + $pnl;
        $wallet->save();

        $position->is_open = false;
        $position->unrealized_pnl = $pnl;
        $position->save();

        return response()->json([
            'message' => 'Position closed',
            'pnl' => $pnl
        ]);
    }

    /**
     * Get live price from CoinGecko service.
     */
    protected function getCurrentPrice($symbol)
    {
        $symbolMap = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'SOL' => 'solana',
            'BNB' => 'binancecoin',
            'ADA' => 'cardano',
            'XRP' => 'ripple',
        ];

        $coinId = $symbolMap[strtoupper($symbol)] ?? null;

        if (!$coinId) return null;

        $prices = $this->coinGecko->getLivePrices([$coinId]);

        return $prices[$coinId]['usd'] ?? null;
    }

    public function getUserPositions(Request $request)
    {
        $user = auth()->user();

        $openPositions = Position::where('user_id', $user->id)
            ->where('is_open', true)
            ->with('wallet')
            ->orderByDesc('created_at')
            ->get();

        $closedPositions = Position::where('user_id', $user->id)
            ->where('is_open', false)
            ->with('wallet')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'open_positions' => $openPositions,
            'closed_positions' => $closedPositions
        ]);
    }

    public function getUserFutureWallets(Request $request)
    {
        $user = auth()->user();

        $futuresWallets = FutureWallet::where('user_id', $user->id)->get();

        return response()->json([
            'futures_wallets' => $futuresWallets
        ]);
    }

    public function getAvailableFuturesCoinsForUser(Request $request, CoinGeckoService $coinGecko)
    {
        $user = auth()->user();

        // 1. Define symbols available for futures
        $futuresSymbols = ['BTC', 'ETH', 'SOL', 'BNB', 'ADA', 'XRP'];

        // 2. Map symbols to CoinGecko IDs
        $symbolToIdMap = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'SOL' => 'solana',
            'BNB' => 'binancecoin',
            'ADA' => 'cardano',
            'XRP' => 'ripple',
        ];

        // 3. Get user’s future wallets
        $userWallets = FutureWallet::where('user_id', $user->id)->get();

        // 4. Uppercase the wallet currencies
        $userWalletSymbols = $userWallets
            ->pluck('currency')
            ->map(fn($symbol) => strtoupper($symbol))
            ->toArray();

        // 5. Determine which of the user's coins are futures-tradable
        $availableUserCoins = array_intersect($userWalletSymbols, $futuresSymbols);

        // 6. Fetch all coins data once
        $allCoins = $coinGecko->getAllCoinsData();

        // 7. Filter only futures coins from allCoins and format response
        $futuresCoinsData = collect($futuresSymbols)->map(function ($symbol) use ($symbolToIdMap, $allCoins, $userWallets) {
            $coinId = $symbolToIdMap[$symbol] ?? null;

            if (!$coinId) {
                return null;
            }

            $coin = $allCoins->firstWhere('id', $coinId);

            if (!$coin) {
                return null;
            }

            // Get balance from user's wallet, or default to 0
            $wallet = $userWallets->firstWhere('currency', strtoupper($symbol));
            $balance = $wallet ? $wallet->balance : '0.00';

            return [
                'id' => $coin['id'] ?? null,
                'name' => $coin['name'] ?? null,
                'symbol' => strtoupper($coin['symbol'] ?? ''),
                'icon' => $coin['icon'] ?? null,
                'price' => $coin['price'] ?? 0,
                'change_rate_percentage' => $coin['change_rate_percentage'] ?? 0,
                'change_rate_usdt' => $coin['change_rate_usdt'] ?? 0,
                'volume' => $coin['volume'] ?? 0,
                'market_cap' => $coin['market_cap'] ?? 0,
                'market_cap_rank' => $coin['market_cap_rank'] ?? null,
                'balance' => (string)$balance,
            ];
        })->filter()->values(); // Remove nulls and reindex

        return response()->json([
            'futures_available_coins' => $futuresCoinsData,
            'user_futures_wallet_coins' => $userWalletSymbols,
            'user_available_to_trade_futures' => $availableUserCoins,
        ]);
    }
}
