<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    protected $newsService;

    public function __construct( NewsService $newsService)
    {
        $this->newsService = $newsService;
    }


    public function getNews()
    {
        $user = Auth::user(); // Get the authenticated user
    
        if (!$user) {
            return response()->json([
                'error' => 'User not authenticated',
                'debug' => request()->headers->all(),
            ], 401);
        }
    
        $news = $this->newsService->getNews();
    
        return response()->json([
            'news' => $news,
        ]);
    }
    
}
