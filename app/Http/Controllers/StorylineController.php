<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\League\StoryLineService;
use Inertia\Inertia;

class StorylineController extends Controller
{
    protected $storyline;

    public function __construct(){
        $this->storyline = new StoryLineService();
    }

    public function generateStoryLine()
    {
        $seasonId = get_current_season_id();
        
        $disabled = false;
        if($disabled){
            return response()->json([
                'message' => 'storyline end-point disabled!',
                'success' => false,
            ],404);
        }

        return $this->storyline->generateStoryLine($seasonId);
    }
}