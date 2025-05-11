<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bank_information;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class BankingInformationController extends Controller
{
    public function index()
    {
        try {
            $bankInformations = Bank_information::where('user_id', Auth::id())->get();
            return response()->json($bankInformations);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to fetch bank information', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'bank_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
                'account_name' => 'required|string|max:255',
            ]);

            $validatedData['user_id'] = Auth::id();

            $bankInformation = Bank_information::create($validatedData);

            return response()->json([
                'message' => 'Bank information added successfully',
                'data' => $bankInformation,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to store bank information', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($userId)
    {
        try {
            $bankInformations = Bank_information::where('user_id', $userId)->get();

            if ($bankInformations->isEmpty()) {
                return response()->json(['message' => 'No bank information found for this user'], 404);
            }

            return response()->json($bankInformations);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error retrieving bank information', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $bankInformation = Bank_information::findOrFail($id);

            if ($bankInformation->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'bank_name' => 'sometimes|required|string|max:255',
                'account_number' => 'sometimes|required|string|max:255',
                'account_name' => 'sometimes|required|string|max:255',
            ]);

            $bankInformation->update($validatedData);

            return response()->json([
                'message' => 'Bank information updated successfully',
                'data' => $bankInformation,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Bank information not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to update bank information', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bankInformation = Bank_information::findOrFail($id);

            if ($bankInformation->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $bankInformation->delete();

            return response()->json(['message' => 'Bank information deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Bank information not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to delete bank information', 'error' => $e->getMessage()], 500);
        }
    }
}
