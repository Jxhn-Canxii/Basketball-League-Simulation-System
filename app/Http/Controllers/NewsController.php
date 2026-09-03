<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\HelperController;
use App\Services\League\NewsService;
class NewsController extends Controller
{
    protected $newsService;

    public function __construct(){
        $this->newsService = new NewsService();
    }
    
    public function getNewsByGameId(Request $request)
    {
        return $this->newsService->getNewsByGameId($request);
    }
   
    public function getAllNews(Request $request)
    {
        return $this->newsService->getAllNews($request);
    }

    public function createGameNewsFromGame($gameId)
    {
        return $this->newsService->createGameNewsFromGame($gameId);
    }
}