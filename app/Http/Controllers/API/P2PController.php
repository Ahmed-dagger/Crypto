<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\P2P;
use Illuminate\Support\Facades\Auth;

class P2PController extends Controller
{
    //----------------------Buy Ad Methods----------------------//
    public function createBuyAd(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.0001',
            'fiat_amount' => 'required|numeric|min:0.01',
            'fiat_currency' => 'required|string|max:10',
            'payment_method' => 'required|string|max:255',
            'payment_details' => 'nullable|string', // <- added
        ]);

        $p2p = P2P::create([
            'user_id' => Auth::id(),
            'trade_type' => 'buy',
            'currency' => $validated['currency'],
            'amount' => $validated['amount'],
            'fiat_amount' => $validated['fiat_amount'],
            'fiat_currency' => $validated['fiat_currency'],
            'payment_method' => $validated['payment_method'],
            'payment_details' => $validated['payment_details'] ?? null, // <- added
            'transfer_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Buy P2P ad created successfully.',
            'data' => $p2p,
        ], 200);
    }


    public function getBuyAds()
    {
        try {
            $buyAds = P2P::with('user:id,name') // eager load user name
                ->where('trade_type', 'buy')
                ->where('user_id', '!=', Auth::id()) // exclude current user's ads
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Buy ads retrieved successfully.',
                'data' => $buyAds,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve buy ads.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editBuyAd(Request $request, $id)
    {
        $validated = $request->validate([
            'currency' => 'sometimes|required|string|max:10',
            'amount' => 'sometimes|required|numeric|min:0.0001',
            'fiat_amount' => 'sometimes|required|numeric|min:0.01',
            'fiat_currency' => 'sometimes|required|string|max:10',
            'payment_method' => 'sometimes|required|string|max:255',
            'payment_details' => 'nullable|string',
        ]);

        $p2p = P2P::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('trade_type', 'buy')
            ->first();

        if (!$p2p) {
            return response()->json(['message' => 'Buy ad not found or unauthorized'], 404);
        }

        $p2p->update($validated);

        return response()->json([
            'message' => 'Buy ad updated successfully.',
            'data' => $p2p,
        ], 200);
    }


    public function deleteBuyAd($id)
    {
        $p2p = P2P::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('trade_type', 'buy')
            ->first();

        if (!$p2p) {
            return response()->json(['message' => 'Buy ad not found or unauthorized'], 404);
        }

        $p2p->delete();

        return response()->json(['message' => 'Buy ad deleted successfully.'], 200);
    }




    //----------------------Sell Ad Methods----------------------//


    public function createSellAd(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.0001',
            'fiat_amount' => 'required|numeric|min:0.01',
            'fiat_currency' => 'required|string|max:10',
            'payment_method' => 'required|string|max:255',
            'payment_details' => 'nullable|string', // <- added
        ]);

        $p2p = P2P::create([
            'user_id' => Auth::id(),
            'trade_type' => 'sell',
            'currency' => $validated['currency'],
            'amount' => $validated['amount'],
            'fiat_amount' => $validated['fiat_amount'],
            'fiat_currency' => $validated['fiat_currency'],
            'payment_method' => $validated['payment_method'],
            'payment_details' => $validated['payment_details'] ?? null, // <- added
            'transfer_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Sell P2P ad created successfully.',
            'data' => $p2p,
        ], 200);
    }

    public function editSellAd(Request $request, $id)
    {
        $validated = $request->validate([
            'currency' => 'sometimes|required|string|max:10',
            'amount' => 'sometimes|required|numeric|min:0.0001',
            'fiat_amount' => 'sometimes|required|numeric|min:0.01',
            'fiat_currency' => 'sometimes|required|string|max:10',
            'payment_method' => 'sometimes|required|string|max:255',
            'payment_details' => 'nullable|string',
        ]);

        $p2p = P2P::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('trade_type', 'sell')
            ->first();

        if (!$p2p) {
            return response()->json(['message' => 'Sell ad not found or unauthorized'], 404);
        }

        $p2p->update($validated);

        return response()->json([
            'message' => 'Sell ad updated successfully.',
            'data' => $p2p,
        ], 200);
    }

    public function deleteSellAd($id)
    {
        $p2p = P2P::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('trade_type', 'sell')
            ->first();

        if (!$p2p) {
            return response()->json(['message' => 'Sell ad not found or unauthorized'], 404);
        }

        $p2p->delete();

        return response()->json(['message' => 'Sell ad deleted successfully.'], 200);
    }


    public function getSellAds()
    {
        try {
            $sellAds = P2P::with('user:id,name') // eager load user name
                ->where('trade_type', 'sell')
                ->where('user_id', '!=', Auth::id()) // exclude current user's ads
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Sell ads retrieved successfully.',
                'data' => $sellAds,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve sell ads.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //-----------------------Trade Methods----------------------//

    public function startTrade(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $p2p = P2P::find($id);

        if (!$p2p) {
            return response()->json(['message' => 'Ad not found.'], 404);
        }

        // Check for expired in-progress trade
        if ($p2p->transfer_status === 'in_progress' && $p2p->started_at) {
            $expired = now()->diffInMinutes($p2p->started_at) >= 15;
            if ($expired) {
                // Reset trade
                $p2p->transfer_status = 'pending';
                $p2p->counterparty_id = null;
                $p2p->taken_amount = null;
                $p2p->started_at = null;
                $p2p->save();
            } else {
                return response()->json(['message' => 'This ad is already in progress.'], 400);
            }
        }

        if ($p2p->user_id === Auth::id()) {
            return response()->json(['message' => 'You cannot accept your own ad.'], 403);
        }

        if ($request->amount > $p2p->amount) {
            return response()->json(['message' => 'Requested amount exceeds available amount.'], 400);
        }

        $p2p->counterparty_id = Auth::id();
        $p2p->transfer_status = 'in_progress';
        $p2p->taken_amount = $request->amount;
        $p2p->started_at = now();
        $p2p->save();

        return response()->json([
            'message' => 'You have successfully accepted this trade.',
            'data' => $p2p,
        ]);
    }

    public function completeTrade(Request $request, $id)
    {
        $p2p = P2P::find($id);

        if (!$p2p) {
            return response()->json(['message' => 'Trade not found.'], 404);
        }

        if ($p2p->trade_type === 'sell' && $p2p->user_id !== Auth::id()) {
            return response()->json(['message' => 'Only the seller can finalize this trade.'], 403);
        }

        if (!$p2p->counterparty_id) {
            return response()->json(['message' => 'This trade has not been accepted yet.'], 400);
        }

        if ($p2p->transfer_status !== 'in_progress') {
            return response()->json(['message' => 'This trade is not in progress.'], 400);
        }

        // Expiration check
        if ($p2p->started_at && now()->diffInMinutes($p2p->started_at) >= 15) {
            // Reset trade
            $p2p->transfer_status = 'pending';
            $p2p->counterparty_id = null;
            $p2p->taken_amount = null;
            $p2p->started_at = null;
            $p2p->save();

            return response()->json([
                'message' => 'This trade expired after 15 minutes. Please start the trade again.',
            ], 408); // 408 Request Timeout
        }

        $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Save payment proof using Spatie
        $p2p->addMediaFromRequest('payment_proof')->toMediaCollection('payment_proofs');

        // Mark trade as complete
        $p2p->transfer_status = 'completed';
        $p2p->save();

        // Deduct the taken amount from total
        $remainingAmount = $p2p->amount - $p2p->taken_amount;
        $remainingFiat = $p2p->fiat_amount - (($p2p->fiat_amount / $p2p->amount) * $p2p->taken_amount);

        $p2p->amount = max($remainingAmount, 0);
        $p2p->fiat_amount = max($remainingFiat, 0);
        $p2p->counterparty_id = null;
        $p2p->taken_amount = null;
        $p2p->started_at = null;
        $p2p->transfer_status = $p2p->amount > 0 ? 'pending' : 'completed';
        $p2p->save();

        return response()->json([
            'message' => 'Trade has been successfully completed.',
            'data' => $p2p,
        ]);
    }

    public function cancelTrade($id)
    {
        $p2p = P2P::find($id);

        if (!$p2p) {
            return response()->json(['message' => 'Trade not found.'], 404);
        }

        // Only ad creator or counterparty can cancel
        if (Auth::id() !== $p2p->user_id && Auth::id() !== $p2p->counterparty_id) {
            return response()->json(['message' => 'Unauthorized to cancel this trade.'], 403);
        }

        // Trade should not already be completed or cancelled
        if (in_array($p2p->transfer_status, ['success', 'cancelled'])) {
            return response()->json(['message' => 'Trade cannot be cancelled.'], 400);
        }

        // Update status
        $p2p->transfer_status = 'cancelled';
        $p2p->save();

        return response()->json([
            'message' => 'Trade has been cancelled successfully.',
            'data' => $p2p,
        ]);
    }

    public function getMyAds()
    {
        $ads = P2P::where('user_id', Auth::id())->get();

        return response()->json([
            'message' => 'Your ads retrieved successfully.',
            'data' => $ads,
        ]);
    }
}
