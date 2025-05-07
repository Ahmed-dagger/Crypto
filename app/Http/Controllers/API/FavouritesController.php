<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
use App\Models\User;
use App\Services\CoinGeckoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FavouritesController extends Controller
{

    protected $coinGeckoService;

    public function __construct(CoinGeckoService $coinGeckoService)
    {   
        $this->coinGeckoService = $coinGeckoService;
    }

    public function show(User $user)
    {
        // Fetch user's favourite coin IDs
        $favourites = Favourite::where('user_id', $user->id)->pluck('currency')->toArray();
    
        if (empty($favourites)) {
            return response()->json([
                'message' => 'No favourite coins found.',
                'data' => [],
            ], 200);
        }
    
        // Fetch all coins data from the service
        $allCoins = $this->coinGeckoService->getAllCoinsData();
    
        // Filter only the user's favourite coins
        $filteredCoins = $allCoins->whereIn('id', $favourites)->values();
    
        return response()->json([
            'message' => 'Success',
            'data' => $filteredCoins,
        ], 200);
    }

    
    public function add(Request $request)
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

public function destroy(User $user, Favourite $favourite , Request $request)
    {
        $target = Favourite::where('user_id', $user->id)
            ->where('id', $favourite->id)
            ->first();

        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'Favourite not found or does not belong to user.'
            ], 404);
        }

        $target->delete();

        return response()->json([
            'success' => true,
            'message' => 'Favourite deleted successfully.'
        ], 200);
    }
}
