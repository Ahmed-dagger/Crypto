<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\PaymobService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymob;

    public function __construct(PaymobService $paymob)
    {
        $this->paymob = $paymob;
    }
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $userId = auth()->id(); // Get authenticated user's ID

        $amountCents = $validated['amount'] * 100; // Convert to cents

        // Step 1: Create Order via Paymob
        $order = $this->paymob->createOrder($amountCents);
        $paymentKey = $this->paymob->paymentKey($order['id'], $amountCents);

        // Step 2: Store Payment Details in Database
        $payment = Payment::create([
            'user_id' => $userId,
            'order_id' => $order['id'],
            'amount' => $validated['amount'],
            'currency' => 'EGP',
            'status' => 'pending',
            'iframe_url' => "https://accept.paymobsolutions.com/api/acceptance/iframes/" . env('PAYMOB_IFRAME_ID') . "?payment_token=" . $paymentKey['token']
        ]);

        // Step 3: Return Payment Link
        return response()->json([
            'iframe_url' => $payment->iframe_url,
            'payment_id' => $payment->id
        ]);
    }

    public function paymobCallback(Request $request)
    {
        $hmacSecret = env('PAYMOB_HMAC'); // Your secret key from .env
        $receivedHmac = $request->hmac;   // HMAC sent by Paymob
    
        // Step 1: Prepare data for HMAC verification
        $data = $request->except('hmac'); // Remove the HMAC key from the request data
        ksort($data); // Sort the data by key
    
        // Concatenate the sorted values to form a single string
        $concatenated = implode('', array_values($data));
    
        // Step 2: Compute HMAC
        $computedHmac = hash_hmac('sha512', $concatenated, $hmacSecret);
    
        // Step 3: Compare the received HMAC with the computed HMAC
        if ($computedHmac !== $receivedHmac) {
            return response()->json(['message' => 'Invalid HMAC'], 403);
        }
    
        // Step 4: Handle the callback logic (successful payment or failure)
        if ($request->success) {
            // Update the payment status in the database
            $payment = Payment::where('order_id', $request->order_id)->first();
            if ($payment) {
                $payment->update([
                    'payment_id' => $request->payment_id,
                    'status' => 'paid',
                ]);
    
                // Step 5: Update the wallet
                $user = $payment->user; // Assuming Payment has a 'user' relationship
                $amount = $payment->amount; // Or use $request->amount if that’s available
    
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0]
                );
    
                $wallet->balance += $amount;
                $wallet->order_id = $payment->order_id;
                $wallet->save();
    
                return response()->json(['message' => 'Payment deposit successful and wallet updated']);
            } else {
                return response()->json(['message' => 'Payment not found'], 404);
            }
        }
    
        // Handle failure if success is false
        return response()->json(['message' => 'Payment failed']);
    }
}
