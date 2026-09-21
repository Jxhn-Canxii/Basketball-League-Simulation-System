<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0); // Unlimited execution time

use Illuminate\Http\Request;
use App\Services\Game\SimulateService;

class SimulateController extends Controller
{
    protected $simulateService;
    
    public function __construct()
    {
        $this->simulateService = new SimulateService();
    }

    public function simulatePlayoff(Request $request)
    {
        // DB::beginTransaction(); // Start transaction

        // try {
        // Validate the request data
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        return $this->simulateService->simulatePlayoff($request);
    }

    public function simulatePlayoffSeries(Request $request)
    {
        // DB::beginTransaction(); // Start transaction

        // try {
        // Validate the request data
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        return $this->simulateService->simulatePlayoffSeries($request);
    }

    public function simulateRegular(Request $request)
    {
        // DB::beginTransaction(); // Start transaction

        // try {
        // Validate the request data
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        return $this->simulateService->simulateRegular($request);
    }

    public function simulateAllStar(Request $request)
    {
        // DB::beginTransaction(); // Start transaction

        // try {
        // Validate the request data
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        return $this->simulateService->simulateAllStar($request);
    }

}
