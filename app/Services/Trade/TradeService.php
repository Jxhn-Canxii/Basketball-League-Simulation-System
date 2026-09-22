<?php

namespace App\Services\Trade;

ini_set('max_execution_time', 0);

use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;
use App\Services\League\StoryLineService;

class TradeService
{
    protected $storyLineService;
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
        
        $this->storyLineService = new StoryLineService();
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function getPendingTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('type', $tradeType)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'trade_type' => $tradeType,
        ]);
    }

    public function getAllTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;
        $seasonId = (int) $request->season_id;

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->orderBy('type')
            ->orderBy('status')
            ->orderByDesc('created_at')
            ->get();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'trade_type' => 'all',
        ]);
    }

    public function getRecentTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;
        $seasonId = (int) $request->season_id;
        $limit = (int) $request->limit;

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('status','!=','rejected')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $proposalCount = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('status','approved')
            ->count();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'proposal_count' => $proposalCount,
            'trade_type' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVED TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function getApprovedTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $latestSeasonStatus = DB::table('seasons')
            ->where('id', DB::table('seasons')->max('id'))
            ->value('status');

        $tradeSeasonEnd =
            config('timeline.off_season_trade') === $latestSeasonStatus;

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('type', $tradeType)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'trade_season_end' => $tradeSeasonEnd,
            'current_status' => $latestSeasonStatus,
            'trade_type' => $tradeType,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTACH TRADE ASSETS
    |--------------------------------------------------------------------------
    */

    private function attachTradePlayers($proposals): void
    {
        if ($proposals->isEmpty()) {
            return;
        }

        $proposalIds = $proposals
            ->pluck('id')
            ->toArray();

        $assets = DB::table('trade_players')
            ->leftJoin(
                'players',
                'trade_players.player_id',
                '=',
                'players.id'
            )
            ->leftJoin(
                'draft_pick_rights',
                'trade_players.draft_pick_right_id',
                '=',
                'draft_pick_rights.id'
            )
            ->join(
                'teams as from_team',
                'trade_players.from_team_id',
                '=',
                'from_team.id'
            )
            ->join(
                'teams as to_team',
                'trade_players.to_team_id',
                '=',
                'to_team.id'
            )
            ->whereIn(
                'trade_players.trade_proposal_id',
                $proposalIds
            )
            ->select(
                'trade_players.id',
                'trade_players.trade_proposal_id',
                'trade_players.player_id',
                'trade_players.draft_pick_right_id',
                'trade_players.from_team_id',
                'trade_players.to_team_id',

                'players.name as player_name',
                'players.role',

                'draft_pick_rights.season_id as pick_season_id',
                'draft_pick_rights.round as pick_round',
                'draft_pick_rights.original_team_id as pick_original_team_id',
                'draft_pick_rights.current_owner_id as pick_current_owner_id',
                'draft_pick_rights.protections as pick_protections',

                'from_team.name as from_team',
                'to_team.name as to_team',

                'from_team.primary_color as from_team_primary_color',
                'from_team.secondary_color as from_team_secondary_color',

                'to_team.primary_color as to_team_primary_color',
                'to_team.secondary_color as to_team_secondary_color'
            )
            ->orderBy('trade_players.id')
            ->get()
            ->groupBy('trade_proposal_id');

        foreach ($proposals as $proposal) {

            $proposal->tradePlayers =
                $assets
                ->get($proposal->id, collect())
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Compatibility
            |--------------------------------------------------------------------------
            */

            $proposal->players =
                $proposal->tradePlayers;

            /*
            |--------------------------------------------------------------------------
            | Convert pick rows into readable names
            |--------------------------------------------------------------------------
            */

            $proposal->tradePlayers =
                $proposal->tradePlayers->map(
                    function ($asset) {

                        if (!empty($asset->draft_pick_right_id)) {

                            $asset->asset_type = 'draft_pick';

                            $asset->player_name =
                                'Draft Pick: ' .
                                $asset->pick_round ??
                                (
                                    'Round ' .
                                    $asset->pick_round .
                                    ' / Season ' .
                                    $asset->pick_season_id
                                );

                            $asset->role = 'draft pick';
                        } else {

                            $asset->asset_type = 'player';
                        }

                        return $asset;
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Teams involved
            |--------------------------------------------------------------------------
            */

            $teams = collect();

            $teamNames = collect();

            foreach ($proposal->tradePlayers as $asset) {

                $teams->push($asset->from_team_id);
                $teams->push($asset->to_team_id);

                $teamNames->push($asset->from_team);
                $teamNames->push($asset->to_team);
            }

            $proposal->teams_involved =
                $teams
                ->filter()
                ->unique()
                ->values();

            $proposal->team_name_involved =
                $teamNames
                ->filter()
                ->unique()
                ->values();

            $proposal->team_count =
                $proposal->team_count
                ?? $proposal->teams_involved->count();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | END IN-SEASON TRADE WINDOW
    |--------------------------------------------------------------------------
    */

    public function endInSeasonTradeWindow()
    {
        $seasonId = get_current_season_id();

        DB::table('seasons')
            ->where(
                'id',
                $seasonId
            )
            ->update([
                'status' =>
                config('timeline.in_season_trade'),
            ]);

        return response()->json([
            'message' =>
            'Trade window ended!',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | END OFF-SEASON TRADE WINDOW
    |--------------------------------------------------------------------------
    |
    | ArchiveService intentionally does NOT belong here.
    |
    */

    public function endOffSeasonTradeWindow()
    {
        $seasonId = get_current_season_id();


        /*
        |--------------------------------------------------------------------------
        | End trade window only.
        |
        | Season archiving should be handled by the season/off-season
        | orchestration service.
        |--------------------------------------------------------------------------
        */

        DB::table('seasons')
            ->where('id',$seasonId)
            ->update(['status' =>config('timeline.off_season_trade'),]);

        
        return response()->json([
            'message' =>
            'Trade window ended!',
        ]);
    }
}
