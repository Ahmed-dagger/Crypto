<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    public function Ads()
{
    $ads = [
        [
            'image_url' => "https://i.imgur.com/E1PDzXE.png",
            'text' => "The Future of Finance is Here!"
        ],
        [
            'image_url' => "https://i.imgur.com/mq2ULlv.png",
            'text' => "Secure. Smart. Profitable."
        ],
        [
            'image_url' => "https://i.imgur.com/6owkrcn.jpeg",
            'text' => "Crypto Made Easy!"
        ]
    ];

    return response()->json([
        'status' => true, 
        'message' => 'Ads retrieved successfully.',
        'ads' => $ads
    ], 200);
}

}
