<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymobService
{
    protected $apiKey;
    protected $integrationId;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('PAYMOB_API_KEY');
        $this->integrationId = env('PAYMOB_INTEGRATION_ID');
        $this->baseUrl = env('PAYMOB_BASE_URL');
    }

    public function authenticate()
    {
        $response = Http::post("{$this->baseUrl}/auth/tokens", [
            'api_key' => $this->apiKey,
        ]);

        return $response->json()['token'];
    }

    public function createOrder($amountCents)
    {
        $token = $this->authenticate();

        $orderResponse = Http::post("{$this->baseUrl}/ecommerce/orders", [
            'auth_token' => $token,
            'delivery_needed' => false,
            'amount_cents' => $amountCents,
            'currency' => 'EGP',
            'merchant_order_id' => time(),
            'items' => []
        ]);

        return $orderResponse->json();
    }

    public function paymentKey($orderId, $amountCents)
    {
        $token = $this->authenticate();

        $response = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
            'auth_token' => $token,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => [
                "apartment" => "NA",
                "email" => "test@example.com",
                "floor" => "NA",
                "first_name" => "John",
                "street" => "NA",
                "building" => "NA",
                "phone_number" => "0123456789",
                "shipping_method" => "NA",
                "postal_code" => "NA",
                "city" => "Cairo",
                "country" => "EG",
                "last_name" => "Doe",
                "state" => "NA",
            ],
            'currency' => 'EGP',
            'integration_id' => $this->integrationId,
        ]);

        return $response->json();
    }
}
