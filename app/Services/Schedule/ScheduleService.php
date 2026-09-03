<?php

namespace App\Services\Schedule;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class ScheduleService
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }

    public function getScheduleCount($teamId, $seasonId)
    {
                
        $scheduleTable = $this->helper->getScheduleDBName($seasonId);

        $count = DB::table($scheduleTable)
            ->where('season_id', $seasonId) // Filter by season_id
            ->where(function ($query) use ($teamId) {
                // Check if team_id is in either home_id or away_id
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->count();

        return $count;
    }
}
