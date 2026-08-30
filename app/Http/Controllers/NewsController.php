<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\HelperController;

class NewsController extends Controller
{
    protected $helper;

    public function __construct(){
        $this->helper = new HelperController();
    }
    /**
     * Get news by game_id.
     *
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNewsByGameId(Request $request)
    {
        $gameId = $request->input('game_id', 0);
        // Validate game_id
        if (!is_numeric($gameId) || $gameId <= 0) {
            return response()->json([
                'error' => 'Invalid game ID',
            ], 400);
        }

        // Fetch news record
        $news = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'winner_id', 'title', 'content', 'created_at', 'updated_at')
            ->where('game_id', $gameId)
            ->first();

        if (!$news) {
            return response()->json([
                'error' => 'No news found for game ID ' . $gameId,
            ], 404);
        }

        return response()->json([
            'data' => $news,
        ], 200);
    }

    /**
     * Get all news with dynamic pagination and search.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllNews(Request $request)
    {
        // Get pagination parameters with defaults
        $currentPage = $request->input('page_num', 1);
        $seasonId = $request->input('season_id', 1);
        $itemsPerPage = $request->input('itemsperpage', 10);
        $search = $request->input('search', '');

        // Validate inputs
        if (!is_numeric($currentPage) || $currentPage < 1) {
            $currentPage = 1;
        }
        if (!is_numeric($itemsPerPage) || $itemsPerPage < 1 || $itemsPerPage > 100) {
            $itemsPerPage = 10;
        }

        // Build query with optional search
        $query = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'winner_id', 'title', 'content', 'created_at', 'updated_at')
            ->where('season_id',$seasonId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        // Calculate total items and pages
        $totalItems = $query->count();
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Apply pagination
        $news = $query->orderBy('created_at', 'desc')
            ->skip(($currentPage - 1) * $itemsPerPage)
            ->take($itemsPerPage)
            ->get();

        return response()->json([
            'data' => $news,
            'current_page' => (int) $currentPage,
            'total_pages' => (int) $totalPages,
            'total_items' => (int) $totalItems,
            'itemsperpage' => (int) $itemsPerPage,
        ], 200);
    }

    public function createGameNewsFromGame($gameId)
    {
        // Fetch game info with winner_id and team details
        $game = DB::table('schedule_view as sv')
            ->select(
                'sv.game_id',
                'sv.season_id',
                'sv.round',
                'sv.home_team_name as home_team',
                'sv.home_id as home_team_id',
                'sv.away_team_name as away_team',
                'sv.away_id as away_team_id',
                'sv.winner_id',
                'sv.winning_name as winner_team',
                'sv.home_score',
                'sv.away_score',
                'sv.conference_id'
            )
            ->where('sv.id', $gameId)
            ->first();

        if (!$game) {
            return;
        }

        // Check if this is the final round of the regular season
        $season = DB::table('seasons')
            ->select('total_regular_games')
            ->where('id', $game->season_id)
            ->first();

        $isLast3Rounds = $season && $game->round >= ($season->total_regular_games - 3);

        // Determine loser and scores
        $loser = $game->winner_id === $game->home_team_id ? $game->away_team : $game->home_team;
        $loserId = $game->winner_id === $game->home_team_id ? $game->away_team_id : $game->home_team_id;
        $winnerScore = $game->winner_id === $game->home_team_id ? $game->home_score : $game->away_score;
        $loserScore = $game->winner_id === $game->home_team_id ? $game->away_score : $game->home_score;
        $scoreMargin = abs($winnerScore - $loserScore);
        
        $game->loser_id = $loserId;
        // Fetch draft pick for the winning team
        $draftPick = DB::table('drafts as d')
            ->join('players as p', 'd.player_id', '=', 'p.id')
            ->select('p.name as player_name', 'd.round', 'd.pick_number', 'd.season_id as draft_season')
            ->where('d.team_id', $game->winner_id)
            ->where('d.season_id', $game->season_id)
            ->first();

        // Fetch team stats from standings_view for both teams
        $winnerStats = DB::table('standings_view as s')
            ->select('s.wins', 's.losses', 's.home_ppg', 's.score_difference', 's.conference_rank', 's.overall_rank', 's.streak_status', 's.is_defending_champion', 's.next_opponent_name', 's.championships', 's.conference_championships', 's.playoff_appearances')
            ->where('s.team_id', $game->winner_id)
            ->where('s.season_id', $game->season_id)
            ->first();

        $loserStats = DB::table('standings_view as s')
            ->select('s.wins', 's.losses', 's.home_ppg', 's.score_difference', 's.conference_rank', 's.overall_rank', 's.streak_status', 's.is_defending_champion', 's.next_opponent_name', 's.championships', 's.conference_championships', 's.playoff_appearances')
            ->where('s.team_id', $loserId)
            ->where('s.season_id', $game->season_id)
            ->first();

        // Fetch top performer for the winning team
        $topPerformer = DB::table('player_game_stats as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->leftJoin('drafts as d', function ($join) use ($game) {
                $join->on('p.id', '=', 'd.player_id')
                    ->where('d.season_id', '=', $game->season_id);
            })
            ->select(
                'p.name as player_name',
                'pgs.points',
                'pgs.rebounds',
                'pgs.assists',
                'pgs.steals',
                'pgs.blocks',
                'd.season_id as draft_season'
            )
            ->where('pgs.game_id', $game->game_id)
            ->where('pgs.team_id', $game->winner_id)
            ->orderBy('pgs.points', 'desc')
            ->orderBy('pgs.rebounds', 'desc')
            ->orderBy('pgs.assists', 'desc')
            ->first();

        // Determine if top performer is a rookie and has a "good" stat line
        $isRookie = $topPerformer && $topPerformer->draft_season == $game->season_id;
        $isGoodStatLine = $topPerformer && (
            ($topPerformer->points >= 20) || // 20+ points
            ($topPerformer->rebounds >= 10) || // 10+ rebounds
            ($topPerformer->assists >= 10) || // 10+ assists
            ($topPerformer->points >= 15 && $topPerformer->rebounds >= 10) || // Double-double
            ($topPerformer->points >= 15 && $topPerformer->assists >= 10)
        );
        $isRareStatLine = $topPerformer && (
            ($topPerformer->points >= 40) || // 40+ points
            ($topPerformer->points >= 10 && $topPerformer->rebounds >= 10 && $topPerformer->assists >= 10) || // Triple-double
            ($topPerformer->rebounds >= 20) || // 20+ rebounds
            ($topPerformer->assists >= 20) || // 20+ assists
            ($topPerformer->steals >= 7) || // 7+ steals
            ($topPerformer->blocks >= 7) // 7+ blocks
        );

        // Determine if this is a top-vs-worst matchup
        $isTopVsWorst = false;
        $maxRank = null;
        if ($winnerStats && $loserStats && $game->conference_id) {
            $maxRank = DB::table('standings_view')
                ->where('conference_id', $game->conference_id)
                ->where('season_id', $game->season_id)
                ->max('conference_rank');
            $isTopVsWorst = ($winnerStats->conference_rank == 1 && $loserStats->conference_rank == $maxRank) ||
                ($loserStats->conference_rank == 1 && $winnerStats->conference_rank == $maxRank);
        }

        // Fetch head-to-head stats
        $headToHead = DB::table('head_to_head as h2h')
            ->select('h2h.win_percentage', 'h2h.points_for')
            ->where('h2h.team_id', $game->winner_id)
            ->where('h2h.opponent_id', $loserId)
            ->first();

        // ---------------------------------------------------------------
        // Playoff series context
        // ---------------------------------------------------------------
        // Regular-season rounds are numeric. Playoff rounds are string keys.
        $isPlayoff = !is_numeric($game->round);

        $playoffSeries = null;
        $seriesWins = 0;
        $seriesLosses = 0;
        $bestOf = null;
        $seriesLength = null;
        $seriesStatus = null;
        $isSeriesClinched = false;
        $isSeriesTied = false;
        $isGame7 = false;
        $isEliminationGame = false;
        $isPotentialClincher = false;

        if ($isPlayoff) {
            $playoffSeries = DB::table('playoff_series')
                ->where('season_id', $game->season_id)
                ->where('round', $game->round)
                ->where(function ($query) use ($game) {
                    $query->where(function ($q) use ($game) {
                        $q->where('home_team_id', $game->winner_id)
                          ->where('away_team_id', $game->loser_id);
                    })->orWhere(function ($q) use ($game) {
                        $q->where('home_team_id', $game->loser_id)
                          ->where('away_team_id', $game->winner_id);
                    });
                })
                ->first();

            if ($playoffSeries) {
                $bestOf = (int) $playoffSeries->best_of;
                $seriesLength = (int) $playoffSeries->series_length;
                $seriesStatus = (int) $playoffSeries->status;

                $homeWins = (int) $playoffSeries->home_wins;
                $awayWins = (int) $playoffSeries->away_wins;

                if ((int) $game->winner_id === (int) $playoffSeries->home_team_id) {
                    $seriesWins = $homeWins;
                    $seriesLosses = $awayWins;
                } else {
                    $seriesWins = $awayWins;
                    $seriesLosses = $homeWins;
                }

                $requiredWins = (int) ceil($bestOf / 2);

                $isSeriesClinched =
                    $seriesStatus === 2 ||
                    !empty($playoffSeries->winner_team_id) ||
                    $seriesWins >= $requiredWins;

                $isSeriesTied = !$isSeriesClinched && $seriesWins === $seriesLosses;

                // For a best-of-7 series, 3-3 is Game 7.
                // More generally, if both teams are one win from the
                // required total, this is the deciding game.
                $isGame7 =
                    !$isSeriesClinched &&
                    $bestOf === 7 &&
                    $seriesWins === 3 &&
                    $seriesLosses === 3;

                $isEliminationGame =
                    !$isSeriesClinched &&
                    $seriesLosses === ($requiredWins - 1);

                $isPotentialClincher =
                    !$isSeriesClinched &&
                    $seriesWins === ($requiredWins - 1);
            }
        }

        // Fetch team chemistry
        $teamInfo = DB::table('team_season_info as tsi')
            ->select('tsi.chemistry')
            ->where('tsi.team_id', $game->winner_id)
            ->where('tsi.season_id', $game->season_id)
            ->first();

        // Fetch injured players for the game
        $injuredPlayers = DB::table('player_game_stats as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->select('p.name as player_name', 'p.injury_type', 'p.injury_recovery_games', 'pgs.team_id')
            ->where('pgs.game_id', $game->game_id)
            ->where('pgs.is_injured', true)
            ->get();

        // Sports-writer headline engine.
        // The goal is variety without losing factual accuracy: headlines react to
        // margin, streaks, rankings, defending champions and the late-season race.
        $headlineTemplates = [];

        $winnerRank = $winnerStats ? $winnerStats->conference_rank : null;
        $loserRank = $loserStats ? $loserStats->conference_rank : null;
        $isNumberOneMatch = $winnerRank && $loserRank &&
            (($winnerRank == 1 && $loserRank == 2) || ($winnerRank == 2 && $loserRank == 1));

        $winnerOnStreak = $winnerStats && preg_match('/W(\d+)/', $winnerStats->streak_status, $wm)
            ? (int) $wm[1] : 0;
        $winnerWasOnSkid = $winnerStats && preg_match('/L(\d+)/', $winnerStats->streak_status, $lm)
            ? (int) $lm[1] : 0;

        if ($winnerStats && $winnerOnStreak >= 3) {
            $headlineTemplates = [
                "{winner} Keeps Rolling, Runs Past {loser} {home_score}-{away_score}",
                "{winner} Makes It {$winnerOnStreak} Straight With Win Over {loser}",
                "{winner} Extends {$winnerOnStreak}-Game Surge, Turns Back {loser}",
                "{winner} Stays Hot, Handles {loser} {home_score}-{away_score}",
                "{winner} Won't Cool Off, Beats {loser} in Round {round}",
                "Another One in the Win Column: {winner} Downs {loser} {home_score}-{away_score}",
                "{winner} Keeps the Pressure On With Victory Over {loser}",
                "{winner} Rides Momentum Past {loser} in Round {round}",
            ];
        } elseif ($winnerWasOnSkid >= 3) {
            $headlineTemplates = [
                "{winner} Stops the Slide, Beats {loser} {home_score}-{away_score}",
                "{winner} Finds Its Footing, Ends {$winnerWasOnSkid}-Game Skid",
                "Relief for {winner}: {home_score}-{away_score} Win Over {loser}",
                "{winner} Answers the Call, Snaps Losing Streak Against {loser}",
                "{winner} Gets Back in the Win Column Against {loser}",
                "A Much-Needed Response: {winner} Takes Down {loser}",
            ];
        } elseif ($isTopVsWorst) {
            $headlineTemplates = [
                "No. 1 {winner} Handles Last-Place {loser} {home_score}-{away_score}",
                "{winner} Takes Care of Business Against {loser}",
                "Top Meets Bottom: {winner} Pulls Away From {loser}",
                "No. 1 {winner} Avoids the Trap Against {loser}",
                "{winner} Keeps Its Foot on the Gas Against {loser}",
                "Class Tells as {winner} Pulls Away From {loser}",
            ];
        } elseif ($isNumberOneMatch) {
            $headlineTemplates = [
                "{winner} Takes Control of the Race, Edges {loser} {home_score}-{away_score}",
                "Battle for No. 1 Goes to {winner} Over {loser}",
                "{winner} Wins the Top-Seed Showdown Against {loser}",
                "Statement Made: {winner} Knocks Off {loser} in Battle for No. 1",
                "{winner} Grabs the Conference Lead With Win Over {loser}",
                "Top of the Table Tightens as {winner} Beats {loser}",
                "No. 1 Is on the Line, and {winner} Comes Through",
            ];
        } elseif ($winnerStats && $winnerStats->is_defending_champion) {
            if ($scoreMargin <= 3) {
                $headlineTemplates = [
                    "Champions {winner} Survive Late Scare From {loser}",
                    "{winner} Escapes {loser} in a Thriller, {home_score}-{away_score}",
                    "Defending Champs {winner} Hold Their Nerve Against {loser}",
                    "{winner} Finds a Way, Squeaking Past {loser}",
                    "Championship Poise: {winner} Edges {loser} in Round {round}",
                    "{winner} Bends, Doesn't Break Against {loser}",
                ];
            } elseif ($scoreMargin <= 7) {
                $headlineTemplates = [
                    "Defending Champs {winner} Get Past {loser} {home_score}-{away_score}",
                    "{winner} Looks the Part, Outplays {loser}",
                    "Reigning Champions {winner} Stay in Command Against {loser}",
                    "{winner} Turns Back {loser} With Another Solid Showing",
                    "Champions' Response: {winner} Handles {loser}",
                    "{winner} Continues Its Title Defense With Win Over {loser}",
                ];
            } else {
                $headlineTemplates = [
                    "{winner} Sends a Message With Rout of {loser}",
                    "Champions Make a Statement: {winner} Runs Away From {loser}",
                    "{winner} Puts on a Clinic Against {loser}",
                    "Defending Champs {winner} Roll to a Convincing Win",
                    "{winner} Flexes Its Championship Muscle Against {loser}",
                    "No Doubt Tonight: {winner} Dominates {loser}",
                ];
            }
        } elseif ($loserStats && $loserStats->is_defending_champion) {
            if ($scoreMargin <= 3) {
                $headlineTemplates = [
                    "{winner} Stuns the Champions in a Down-to-the-Wire Finish",
                    "Defending Champs {loser} Fall as {winner} Steals a Thriller",
                    "{winner} Delivers a Fourth-Quarter Gut Punch to {loser}",
                    "Champions Pushed Aside: {winner} Edges {loser}",
                    "{winner} Holds Its Nerve, Knocking Off {loser}",
                    "Big Win, Bigger Statement: {winner} Beats the Reigning Champs",
                ];
            } elseif ($scoreMargin <= 7) {
                $headlineTemplates = [
                    "{winner} Hands Defending Champs {loser} a Costly Loss",
                    "{winner} Outworks Reigning Champs {loser} in Round {round}",
                    "Champions Stumble as {winner} Controls the Finish",
                    "{winner} Takes Down {loser} in a Statement Result",
                    "A Warning Shot: {winner} Beats Defending Champion {loser}",
                    "{winner} Adds Pressure to {loser}'s Title Defense",
                ];
            } else {
                $headlineTemplates = [
                    "{winner} Runs Defending Champs {loser} Out of the Building",
                    "Champions Crushed: {winner} Rolls Past {loser}",
                    "{winner} Delivers a Stunning Rout of Defending Champion {loser}",
                    "Title Defense Takes a Hit as {winner} Dominates {loser}",
                    "{winner} Sends Shockwaves With Blowout of {loser}",
                    "Reigning Champs {loser} Routed in Statement Loss to {winner}",
                ];
            }
        } elseif ($scoreMargin <= 3) {
            $headlineTemplates = [
                "{winner} Survives {loser} in a Thriller, {home_score}-{away_score}",
                "{winner} Wins the One That Came Down to the Wire",
                "{winner} Holds Off {loser} in a Nail-Biter",
                "Down to the Final Possession: {winner} Edges {loser}",
                "{winner} Slips Past {loser} in a Heart-Stopping Finish",
                "{winner} Finds a Way Against {loser} in Round {round}",
                "Late-Game Drama Ends With {winner} on Top",
                "{winner} Escapes With a Hard-Fought Win Over {loser}",
                "One Possession, One Winner: {winner} Takes Down {loser}",
                "{winner} Keeps Its Cool in Tight Win Over {loser}",
            ];
        } elseif ($scoreMargin <= 7) {
            $headlineTemplates = [
                "{winner} Outlasts {loser} {home_score}-{away_score} in Round {round}",
                "{winner} Takes Care of {loser} in a Hard-Fought Battle",
                "{winner} Pulls Away Late to Beat {loser}",
                "{winner} Controls the Finish, Beats {loser}",
                "{winner} Wins the Battle With {loser} {home_score}-{away_score}",
                "{winner} Does Just Enough to Get Past {loser}",
                "{winner} Turns in a Complete Performance Against {loser}",
                "{winner} Earns a Gritty Round {round} Victory Over {loser}",
                "{winner} Keeps {loser} at Arm's Length in Solid Win",
            ];
        } else {
            $headlineTemplates = [
                "{winner} Runs Away From {loser} in Round {round} Rout",
                "{winner} Puts on a Show in Blowout of {loser}",
                "{winner} Turns the Game Into a Statement Against {loser}",
                "{winner} Rolls Past {loser} {home_score}-{away_score}",
                "{winner} Overwhelms {loser} From Start to Finish",
                "{winner} Leaves No Doubt in Dominant Win Over {loser}",
                "{winner} Turns on the Jets, Blows Past {loser}",
                "{winner} Delivers a One-Sided Lesson Against {loser}",
                "{winner} Runs Riot Against {loser} in Round {round}",
                "{winner} Takes Control Early, Never Looks Back Against {loser}",
            ];
        }

        // ---------------------------------------------------------------
        // Article narrative engine
        // ---------------------------------------------------------------
        // Playoffs get their own vocabulary and story angles. Regular-season
        // games continue using the existing lead / angle / close engine.
        $contentStarters = [];
        $contentMiddles = [];
        $contentEnders = [];

        $roundLabel = $this->helper->roundFormatter($game->round);
        $winnerRecord = $winnerStats ? "{$winnerStats->wins}-{$winnerStats->losses}" : "their current record";
        $loserRecord = $loserStats ? "{$loserStats->wins}-{$loserStats->losses}" : "their current record";

        if ($isPlayoff) {
            // -----------------------------------------------------------
            // PLAYOFF HEADLINES
            // -----------------------------------------------------------
            $headlineTemplates = [];

            if ($isSeriesClinched) {
                $headlineTemplates = [
                    "{winner} Closes Out {loser}, Advances From {$roundLabel}",
                    "{winner} Finishes the Job, Eliminates {loser}",
                    "Series Over: {winner} Knocks Out {loser} in {$roundLabel}",
                    "{winner} Punches Its Ticket to the Next Round",
                    "{winner} Completes the Series Win Over {loser}",
                    "No Tomorrow for {loser} as {winner} Clinches {$roundLabel} Series",
                    "{winner} Gets the Job Done, Sends {loser} Home",
                    "Closeout Night: {winner} Ends {loser}'s Playoff Run",
                ];
            } elseif ($isPotentialClincher) {
                $headlineTemplates = [
                    "{winner} Moves Within One Win of Eliminating {loser}",
                    "{winner} Takes Command of {$roundLabel} Series Against {loser}",
                    "{winner} Puts {loser} on the Brink of Elimination",
                    "{winner} Seizes Control as {$roundLabel} Series Tightens",
                    "One Win Away: {winner} Takes {$seriesWins}-{$seriesLosses} Series Lead",
                    "{loser} Faces Elimination After Loss to {winner}",
                    "{winner} Takes a Huge Step Toward Advancing",
                ];
            } elseif ($seriesWins == $seriesLosses) {
                $headlineTemplates = [
                    "{winner} Breaks {$roundLabel} Series Tie With Win Over {loser}",
                    "Series Level No More: {winner} Takes the Lead Against {loser}",
                    "{winner} Grabs Control After {$roundLabel} Series Deadlock",
                    "{winner} Takes {$seriesWins}-{$seriesLosses} Series Lead Over {loser}",
                    "Postseason Battle Tilts Toward {winner}",
                    "{winner} Lands a Crucial Blow in Even {$roundLabel} Series",
                ];
            } elseif ($seriesWins > $seriesLosses) {
                $headlineTemplates = [
                    "{winner} Adds to {$seriesWins}-{$seriesLosses} {$roundLabel} Series Lead",
                    "{winner} Keeps Its Foot on the Gas Against {loser}",
                    "{winner} Moves Closer to Advancing With Another Playoff Win",
                    "{winner} Protects Series Advantage Against {loser}",
                    "Playoff Pressure Builds as {winner} Beats {loser} Again",
                    "{winner} Keeps {loser} on the Back Foot in {$roundLabel}",
                ];
            } else {
                $headlineTemplates = [
                    "{winner} Fights Back, Cuts Into {loser}'s Series Lead",
                    "{winner} Keeps Its Playoff Run Alive Against {loser}",
                    "{winner} Answers the Call With Crucial {$roundLabel} Win",
                    "{winner} Refuses to Go Quietly, Beats {loser}",
                    "{winner} Gives {loser} a Playoff Wake-Up Call",
                    "{winner} Keeps the Series From Slipping Away",
                ];
            }

            // Deciding Game 7 gets its own headline language.
            if ($isGame7) {
                $headlineTemplates = [
                    "{winner} Wins the Game 7 Battle, Eliminates {loser}",
                    "Game 7 Belongs to {winner} as {loser} Falls",
                    "{winner} Survives Game 7, Advances in the Playoffs",
                    "Winner Takes All: {winner} Beats {loser} in Game 7",
                    "Game 7 Glory: {winner} Ends {loser}'s Playoff Run",
                    "{winner} Delivers When It Matters Most, Wins Game 7",
                    "No Tomorrow for {loser} as {winner} Takes Game 7",
                ];
            }

            // Special Finals language.
            if ($game->round === 'finals') {
                if ($isGame7) {
                    $headlineTemplates = [
                        "{winner} Wins Game 7, Claims the Championship",
                        "Game 7 Glory: {winner} Are Champions",
                        "{winner} Wins It All After Surviving a Finals Game 7",
                        "Winner Takes the Crown: {winner} Beat {loser} in Game 7",
                        "Championship Game 7 Belongs to {winner}",
                    ];
                } elseif ($isSeriesClinched) {
                    $headlineTemplates = [
                        "{winner} Wins It All, Finishes Off {loser} in The Finals",
                        "{winner} Are Champions: {loser} Falls in The Finals",
                        "The Crown Belongs to {winner}",
                        "{winner} Clinch the Championship Against {loser}",
                        "Championship Secured: {winner} Finish the Job",
                        "{winner} Complete the Championship Run",
                    ];
                } elseif ($isPotentialClincher) {
                    $headlineTemplates = [
                        "{winner} One Win From the Championship After Beating {loser}",
                        "{winner} Take Control of The Finals Against {loser}",
                        "Championship Within Reach as {winner} Beat {loser}",
                        "{loser} on the Brink as {winner} Take Finals Lead",
                        "{winner} Move to the Doorstep of a Championship",
                    ];
                }
            }

            if ($isGame7 && $topPerformer) {
                $contentStarters = [
                    "Seven games came down to one night, and {winner} delivered when the season was on the line. {player} led the way with {points} points, {rebounds} rebounds and {assists} assists.",
                    "Everything was on the line in Game 7, and {winner} found a way to survive {loser}. {player} stepped into the pressure with {points} points and {assists} assists.",
                    "There was no tomorrow for either team, and {winner} handled the moment. {player} finished with {points} points, {rebounds} rebounds and {assists} assists in the deciding game.",
                    "A seven-game battle needed a winner, and {winner} finally separated from {loser} in the biggest game of the series.",
                ];
            } elseif ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "The postseason spotlight found rookie {player}, who delivered {points} points, {rebounds} rebounds and {assists} assists as {winner} beat {loser} {home_score}-{away_score}.",
                    "Rookie {player} looked comfortable under playoff pressure, producing {points} points and {assists} assists to help {winner} take down {loser}.",
                    "First-year standout {player} turned in a playoff performance well beyond his experience, leading {winner} with {points} points against {loser}.",
                    "When the stakes rose, rookie {player} answered. The first-year player finished with {points} points, {rebounds} rebounds and {assists} assists in the victory.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "Playoff basketball demands stars, and {player} delivered. The standout posted {points} points, {rebounds} rebounds and {assists} assists to lead {winner} past {loser}.",
                    "{player} authored the defining performance of the night, pouring in {points} points as {winner} took another step forward in {$roundLabel}.",
                    "With the pressure turned all the way up, {player} responded with {points} points, {rebounds} rebounds and {assists} assists for {winner}.",
                    "{player} was at the center of everything for {winner}, producing {points} points and {assists} assists when the postseason demanded it.",
                ];
            } elseif ($scoreMargin <= 3) {
                $contentStarters = [
                    "This was playoff basketball in its purest form: tight, physical and decided by the smallest details. {winner} escaped {loser} {home_score}-{away_score}.",
                    "There was nowhere to hide in this one. {winner} and {loser} traded blows throughout before {winner} finally secured the narrow victory.",
                    "The pressure was obvious from the opening tip, and neither team could create separation. {winner} made the final plays count.",
                    "{winner} survived a postseason nail-biter, holding off {loser} in a game that came down to the final possessions.",
                    "Every possession carried weight, and {winner} handled the pressure just a little better than {loser} when it mattered most.",
                ];
            } else {
                $contentStarters = [
                    "{winner} took control of the postseason matchup early and never surrendered it, defeating {loser} {home_score}-{away_score}.",
                    "The playoff intensity was there, but so was the control from {winner}, which steadily pulled away from {loser}.",
                    "{winner} delivered a composed postseason performance, beating {loser} and putting another important win on the playoff ledger.",
                    "The deeper the game went, the more {winner} imposed its style, eventually creating enough separation to put {loser} away.",
                    "{winner} played like a team that understood the stakes, taking care of {loser} and strengthening its position in {$roundLabel}.",
                ];
            }

            // Playoff-specific middle angles.
            $contentMiddles = [];

            if ($isGame7) {
                $contentMiddles = [
                    "The series could not have gone further, and {winner} made the decisive plays in the winner-take-all finale.",
                    "After six games of back-and-forth basketball, the series finally had its answer: {winner} advances, while {loser} goes home.",
                    "The pressure was as high as it gets, with both teams entering the night one win from the next round.",
                    "Game 7 leaves no room for excuses. {winner} executed under the brightest postseason pressure and earned the right to continue.",
                ];
            } elseif ($isSeriesClinched) {
                $contentMiddles = [
                    "The victory closes the series and sends {winner} through, while {loser}'s postseason comes to an end.",
                    "There is no next game for {loser}; {winner} has officially won the series and advances.",
                    "{winner} handled the pressure of closing the door, ending the series when the opportunity arrived.",
                    "The decisive win turns the series in {winner}'s favor permanently, with the club now preparing for the next round.",
                    "For {loser}, it is a difficult ending. For {winner}, the playoff road continues.",
                ];
            } elseif ($isPotentialClincher) {
                $contentMiddles = [
                    "{winner} now holds the leverage, sitting one win away from advancing while {loser} faces a must-win situation.",
                    "The pressure has shifted squarely onto {loser}, which now needs a response to keep its postseason alive.",
                    "{winner} has put itself in position to finish the series, but the job is not complete yet.",
                    "The next game could carry enormous consequences, with {winner} one victory from moving on.",
                    "This is where playoff series can change quickly: {winner} has the advantage, while {loser} is fighting to extend the matchup.",
                ];
            } elseif ($seriesWins == $seriesLosses) {
                $contentMiddles = [
                    "The series is now tilted toward {winner}, but neither side has created meaningful separation yet.",
                    "With the matchup still finely balanced, the next game becomes another test of adjustments and execution.",
                    "Neither team has been able to put the other away, making every remaining game increasingly valuable.",
                    "The series has developed into the kind of chess match where one strong performance can change everything.",
                ];
            } elseif ($seriesWins > $seriesLosses) {
                $contentMiddles = [
                    "{winner} continues to hold the series advantage, forcing {loser} to find answers before the next game.",
                    "The series is moving in {winner}'s direction, but the margin for error remains thin in a playoff setting.",
                    "{winner} has built valuable momentum, while {loser} now has to respond before the deficit grows.",
                    "The win gives {winner} another piece of leverage as the series moves deeper into the postseason.",
                ];
            } else {
                $contentMiddles = [
                    "The result keeps {winner}'s postseason hopes alive and prevents {loser} from putting the series away.",
                    "{winner} needed a response, and tonight provided it. The pressure remains, but the series is still alive.",
                    "For {winner}, this was about survival as much as victory. The team now gets another chance to extend its run.",
                    "The loss keeps the matchup within reach for {winner}, which can now carry this momentum into the next game.",
                ];
            }

            if ($winnerStats && $winnerStats->is_defending_champion) {
                $contentMiddles[] = "The defending champions continue to navigate the pressure that comes with having the crown on their heads.";
            }

            if ($loserStats && $loserStats->is_defending_champion) {
                $contentMiddles[] = "For the defending champions, the loss adds another layer of pressure to an already demanding postseason.";
            }

            // Playoff closing lines.
            $contentEnders = [];

            if ($game->round === 'finals') {
                if ($isGame7) {
                    $contentEnders = [
                        "The seven-game battle is over, and {winner} has earned the right to call itself champion.",
                        "After the longest possible road, {winner} stands alone at the top.",
                        "The season ends at the ultimate stage with {winner} holding the championship.",
                    ];
                } elseif ($isSeriesClinched) {
                    $contentEnders = [
                        "The season ends with {winner} on top, and the championship now belongs to the team that survived the entire postseason journey.",
                        "{winner} has reached the summit. The trophy, the celebration and the title are now theirs.",
                        "The final chapter has been written: {winner} are champions.",
                        "For {winner}, every playoff win led to this moment. The championship is finally secured.",
                    ];
                } else {
                    $contentEnders = [
                        "The Finals continue, and the championship race is far from settled.",
                        "The next Finals game now carries even greater weight as both teams chase the crown.",
                        "There is no shortage of pressure from here. One team is trying to finish the job; the other is trying to take the championship away.",
                        "The title remains within reach, but the next chapter promises another battle.",
                    ];
                }
            } elseif ($isSeriesClinched) {
                $contentEnders = [
                    "{winner} moves on, while {loser} begins the difficult process of looking ahead to next season.",
                    "The bracket moves forward with {winner}; for {loser}, the playoff journey ends here.",
                    "One team advances and one team goes home. Tonight, that team is {winner}.",
                    "{winner} gets to keep playing. {loser}'s postseason story is over.",
                ];
            } elseif ($isPotentialClincher) {
                $contentEnders = [
                    "The series is not over, but {winner} now has the advantage every playoff team wants: a chance to finish the job.",
                    "{loser} will need its best response yet, while {winner} prepares for an opportunity to close things out.",
                    "The next game could define the series. {winner} has the momentum; {loser} has the urgency.",
                ];
            } else {
                $contentEnders = [
                    "The series moves on, and the margin for error gets smaller with every game.",
                    "There is no time to dwell on the result. Both teams now turn toward the next playoff battle.",
                    "The postseason waits for no one. The next game will bring another test, another adjustment and another chance to take control.",
                    "One result has been decided, but the series still has another chapter waiting.",
                ];
            }

            // Keep the existing regular-season engine below untouched.
        } else {
            // -----------------------------------------------------------
            // REGULAR SEASON ENGINE
            // -----------------------------------------------------------
            $headlineTemplates = [];

            $winnerOnStreak = $winnerStats && preg_match('/W(\d+)/', $winnerStats->streak_status, $wm)
                ? (int) $wm[1] : 0;
            $winnerWasOnSkid = $winnerStats && preg_match('/L(\d+)/', $winnerStats->streak_status, $lm)
                ? (int) $lm[1] : 0;

            $isNumberOneMatch = $winnerStats && $loserStats &&
                (($winnerStats->conference_rank == 1 && $loserStats->conference_rank == 2) ||
                 ($winnerStats->conference_rank == 2 && $loserStats->conference_rank == 1));

            if ($winnerOnStreak >= 3) {
                $headlineTemplates = [
                    "{winner} Keeps Rolling, Runs Past {loser} {home_score}-{away_score}",
                    "{winner} Makes It {$winnerOnStreak} Straight With Win Over {loser}",
                    "{winner} Extends {$winnerOnStreak}-Game Surge, Turns Back {loser}",
                    "{winner} Stays Hot, Handles {loser} {home_score}-{away_score}",
                    "{winner} Won't Cool Off, Beats {loser} in Round {round}",
                ];
            } elseif ($winnerWasOnSkid >= 3) {
                $headlineTemplates = [
                    "{winner} Stops the Slide, Beats {loser} {home_score}-{away_score}",
                    "{winner} Finds Its Footing, Ends {$winnerWasOnSkid}-Game Skid",
                    "Relief for {winner}: {home_score}-{away_score} Win Over {loser}",
                    "{winner} Answers the Call, Snaps Losing Streak Against {loser}",
                ];
            } elseif ($isNumberOneMatch) {
                $headlineTemplates = [
                    "{winner} Takes Control of the Race, Edges {loser} {home_score}-{away_score}",
                    "Battle for No. 1 Goes to {winner} Over {loser}",
                    "{winner} Wins the Top-Seed Showdown Against {loser}",
                    "Statement Made: {winner} Knocks Off {loser} in Battle for No. 1",
                ];
            } elseif ($winnerStats && $winnerStats->is_defending_champion) {
                if ($scoreMargin <= 3) {
                    $headlineTemplates = [
                        "Champions {winner} Survive Late Scare From {loser}",
                        "{winner} Escapes {loser} in a Thriller, {home_score}-{away_score}",
                        "Defending Champs {winner} Hold Their Nerve Against {loser}",
                    ];
                } elseif ($scoreMargin <= 7) {
                    $headlineTemplates = [
                        "Defending Champs {winner} Get Past {loser} {home_score}-{away_score}",
                        "{winner} Looks the Part, Outplays {loser}",
                        "Reigning Champions {winner} Stay in Command Against {loser}",
                    ];
                } else {
                    $headlineTemplates = [
                        "{winner} Sends a Message With Rout of {loser}",
                        "Champions Make a Statement: {winner} Runs Away From {loser}",
                        "Defending Champs {winner} Roll to a Convincing Win",
                    ];
                }
            } elseif ($loserStats && $loserStats->is_defending_champion) {
                $headlineTemplates = [
                    "{winner} Stuns the Champions in a Down-to-the-Wire Finish",
                    "{winner} Hands Defending Champs {loser} a Costly Loss",
                    "A Warning Shot: {winner} Beats Defending Champion {loser}",
                    "{winner} Sends Shockwaves With Win Over {loser}",
                ];
            } elseif ($scoreMargin <= 3) {
                $headlineTemplates = [
                    "{winner} Survives {loser} in a Thriller, {home_score}-{away_score}",
                    "{winner} Holds Off {loser} in a Nail-Biter",
                    "Down to the Final Possession: {winner} Edges {loser}",
                    "{winner} Slips Past {loser} in a Heart-Stopping Finish",
                ];
            } elseif ($scoreMargin <= 7) {
                $headlineTemplates = [
                    "{winner} Outlasts {loser} {home_score}-{away_score}",
                    "{winner} Takes Care of {loser} in a Hard-Fought Battle",
                    "{winner} Pulls Away Late to Beat {loser}",
                    "{winner} Earns a Gritty Victory Over {loser}",
                ];
            } else {
                $headlineTemplates = [
                    "{winner} Runs Away From {loser} in a Rout",
                    "{winner} Puts on a Show in Blowout of {loser}",
                    "{winner} Rolls Past {loser} {home_score}-{away_score}",
                    "{winner} Leaves No Doubt in Dominant Win Over {loser}",
                ];
            }

            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie {player} stole the spotlight in {$roundLabel}, finishing with {points} points, {rebounds} rebounds and {assists} assists as {winner} beat {loser}.",
                    "First-year standout {player} delivered {points} points and {assists} assists, helping {winner} pull away from {loser}.",
                    "{player} continues to make an impression, leading {winner} with {points} points in the victory.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "{player} authored the game's biggest individual performance, finishing with {points} points, {rebounds} rebounds and {assists} assists for {winner}.",
                    "This was a statement night from {player}, who erupted for {points} points as {winner} beat {loser}.",
                    "The numbers tell the story: {player} posted {points} points, {rebounds} rebounds and {assists} assists.",
                ];
            } elseif ($scoreMargin <= 3) {
                $contentStarters = [
                    "For nearly the entire night, neither side could create separation. In the end, {winner} found just enough to edge {loser} {home_score}-{away_score}.",
                    "It was the kind of game that came down to execution, and {winner} made one more play than {loser}.",
                    "The scoreboard never had much breathing room. {winner} held its nerve and slipped past {loser}.",
                ];
            } else {
                $contentStarters = [
                    "{winner} took control and never gave {loser} a realistic opening, earning a {home_score}-{away_score} win.",
                    "There was little suspense after the opening stretch as {winner} steadily pulled away from {loser}.",
                    "{winner} delivered a steady performance and came away with a {home_score}-{away_score} victory over {loser}.",
                    "The matchup stayed competitive for stretches, but {winner} consistently answered {loser}'s pushes.",
                ];
            }

            if ($winnerStats && $winnerOnStreak >= 3) {
                $contentMiddles = [
                    "{winner} has now won {$winnerOnStreak} in a row, and the club is beginning to build real momentum.",
                    "That victory extended {winner}'s winning streak to {$winnerOnStreak}, giving the team another reason to believe.",
                ];
            } elseif ($winnerStats && $winnerWasOnSkid >= 3) {
                $contentMiddles = [
                    "Most importantly, the result snaps a {$winnerWasOnSkid}-game skid and gives {winner} some breathing room.",
                    "After {$winnerWasOnSkid} straight losses, {winner} finally gets a response game.",
                ];
            } elseif ($winnerStats && $winnerStats->score_difference > 50) {
                $contentMiddles = [
                    "The season-long numbers back up the eye test: {winner} entered with a {score_difference}-point scoring margin.",
                    "{winner}'s +{score_difference} scoring differential suggests this result was not an isolated performance.",
                ];
            } elseif ($headToHead && $headToHead->win_percentage > 0.6) {
                $contentMiddles = [
                    "The result also continues a favorable matchup trend, with {winner} holding a {win_percentage}% head-to-head win rate against {loser}.",
                    "{winner}'s historical edge over {loser} remains intact.",
                ];
            } elseif ($teamInfo && $teamInfo->chemistry > 80) {
                $contentMiddles = [
                    "The cohesion was evident, with {winner}'s chemistry rating sitting at {chemistry}.",
                    "High chemistry has been a calling card for {winner}, and the group's {chemistry} rating showed up in its execution.",
                ];
            } else {
                $contentMiddles = [
                    "{winner} improves to {wins}-{losses}, keeping the team moving in the right direction.",
                    "The victory gives {winner} another important entry in the win column.",
                    "The standings will ultimately decide the season, but {winner} leaves this round with a useful result.",
                    "One game rarely defines a season, but this was a valuable step for {winner}.",
                ];
            }

            $contentEnders = [
                "{winner} will now look to build on the result as the regular-season schedule continues.",
                "There is still plenty of basketball left, but {winner} can bank this one and move on.",
                "It is only one game, but it is one {winner} will be happy to have in the standings.",
                "For now, the story belongs to {winner}; the season still has plenty of chapters left to write.",
            ];

            if ($winnerStats && $winnerStats->conference_rank == 1) {
                $contentEnders[] = "{winner} remains at the top of the conference and will now try to hold that position.";
            } elseif ($winnerStats && $winnerStats->conference_rank <= 6) {
                $contentEnders[] = "The result keeps {winner} in strong playoff position and gives the team another opportunity to climb.";
            }

            if ($winnerStats && $winnerStats->playoff_appearances > 5) {
                $contentEnders[] = "The franchise's postseason experience showed in the way {winner} managed the game.";
            }

            if ($winnerStats && $winnerStats->championships > 0) {
                $contentEnders[] = "With championship banners already in its history, {winner} knows regular-season wins serve a bigger goal.";
            }
        }

        // Shared placeholder replacement for both regular-season and playoff stories.
        $replace = function ($template) use (
            $game, $loser, $topPerformer, $winnerStats, $headToHead,
            $teamInfo, $draftPick, $roundLabel
        ) {
            return str_replace(
                [
                    '{round}', '{winner}', '{loser}', '{home_score}', '{away_score}',
                    '{player}', '{points}', '{rebounds}', '{assists}', '{ppg}',
                    '{win_percentage}', '{chemistry}', '{score_difference}',
                    '{wins}', '{losses}', '{conference_rank}'
                ],
                [
                    $roundLabel,
                    $game->winner_team,
                    $loser,
                    $game->home_score,
                    $game->away_score,
                    $topPerformer ? $topPerformer->player_name : ($draftPick ? $draftPick->player_name : 'the standout performer'),
                    $topPerformer ? $topPerformer->points : 'N/A',
                    $topPerformer ? $topPerformer->rebounds : 'N/A',
                    $topPerformer ? $topPerformer->assists : 'N/A',
                    $winnerStats ? number_format($winnerStats->home_ppg, 1) : 'N/A',
                    $headToHead ? number_format($headToHead->win_percentage * 100, 1) : 'N/A',
                    $teamInfo ? $teamInfo->chemistry : 'N/A',
                    $winnerStats ? $winnerStats->score_difference : 'N/A',
                    $winnerStats ? $winnerStats->wins : 'N/A',
                    $winnerStats ? $winnerStats->losses : 'N/A',
                    $winnerStats ? $winnerStats->conference_rank : 'N/A',
                ],
                $template
            );
        };

        // Playoff-specific placeholders.
        $replacePlayoff = function ($template) use (
            $replace,
            $seriesWins,
            $seriesLosses,
            $bestOf,
            $seriesLength
        ) {
            return str_replace(
                [
                    '{series_wins}',
                    '{series_losses}',
                    '{best_of}',
                    '{series_length}',
                ],
                [
                    $seriesWins,
                    $seriesLosses,
                    $bestOf ?? 'N/A',
                    $seriesLength ?? 'N/A',
                ],
                $replace($template)
            );
        };

        $title = $isPlayoff
            ? $replacePlayoff($headlineTemplates[array_rand($headlineTemplates)])
            : $replace($headlineTemplates[array_rand($headlineTemplates)]);

        $content = $isPlayoff
            ? $replacePlayoff($contentStarters[array_rand($contentStarters)])
                . ' ' . $replacePlayoff($contentMiddles[array_rand($contentMiddles)])
                . ' ' . $replacePlayoff($contentEnders[array_rand($contentEnders)])
            : $replace($contentStarters[array_rand($contentStarters)])
                . ' ' . $replace($contentMiddles[array_rand($contentMiddles)])
                . ' ' . $replace($contentEnders[array_rand($contentEnders)]);

        // Build injury content
        $injuryContent = '';
        $winnerInjured = $injuredPlayers->where('team_id', $game->winner_id);
        $loserInjured = $injuredPlayers->where('team_id', $loserId);

        if ($winnerInjured->count() > 0) {
            $winnerInjuryList = $winnerInjured->map(function ($player) {
                $injuryType = $player->injury_type
                    ? ucwords(str_replace('_', ' ', $player->injury_type))
                    : 'an undisclosed issue';

                $recoveryStatus = ($player->injury_recovery_games == 0 || is_null($player->injury_type))
                    ? 'is expected to return next game'
                    : 'is expected to miss ' . $player->injury_recovery_games . ' games';

                return $player->player_name . ' (' . $injuryType . ', ' . $recoveryStatus . ')';
            })->implode(', ');

            $injuryContent .= " The win came with an added challenge, as {$game->winner_team} played through injuries to $winnerInjuryList.";
        }

        if ($loserInjured->count() > 0) {
            $loserInjuryList = $loserInjured->map(function ($player) {
                $injuryType = $player->injury_type
                    ? ucwords(str_replace('_', ' ', $player->injury_type))
                    : 'an undisclosed issue';

                $recoveryStatus = ($player->injury_recovery_games == 0 || is_null($player->injury_type))
                    ? 'is expected to return next game'
                    : 'is expected to miss ' . $player->injury_recovery_games . ' games';

                return $player->player_name . ' (' . $injuryType . ', ' . $recoveryStatus . ')';
            })->implode(', ');

            $injuryContent .= " On the other side, {$loser} was dealing with injuries to $loserInjuryList.";
        }

        // Append injury content to main content
        $content .= $injuryContent;

        // Insert into game_news with winner_id
        DB::table('game_news')->insert([
            'game_id'    => $game->game_id,
            'season_id'  => $game->season_id,
            'round'      => $game->round,
            'winner_id'  => $game->winner_id,
            'title'      => $title,
            'content'    => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}