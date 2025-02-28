<?php

namespace App\Services;

use Binance\API;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NewsService
{
    protected $newsUrl;

    public function __construct()
    {
        $this->newsUrl = 'https://min-api.cryptocompare.com/data/v2/news/';
    }

    public function getNews()
    {
        
        $Response = Http::get($this->newsUrl);

        if ($Response->successful()) {
            return $Response->json(); // Return raw JSON, NOT a response()
        }

        if ($Response->failed())
        {
            return ['error' => 'Failed to fetch news'];
        }

         // Return array, NOT response()
    }
}
