<?php

namespace App\Services\League;

use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class NewsService
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }

    public function getNewsByGameId($request)
    {
        $gameId = $request->input("game_id", 0);

        if (!is_numeric($gameId) || $gameId <= 0) {
            return response()->json(
                [
                    "error" => "Invalid game ID",
                ],
                400
            );
        }

        $news = DB::table("game_news")
            ->select(
                "id",
                "game_id",
                "season_id",
                "round",
                "winner_id",
                "title",
                "content",
                "created_at",
                "updated_at"
            )
            ->where("game_id", $gameId)
            ->first();

        if (!$news) {
            return response()->json(
                [
                    "error" => "No news found for game ID " . $gameId,
                ],
                404
            );
        }

        return response()->json(
            [
                "data" => $news,
            ],
            200
        );
    }

    public function getAllNews($request)
    {
        $currentPage = $request->input("page_num", 1);
        $seasonId = $request->input("season_id", 1);
        $itemsPerPage = $request->input("itemsperpage", 10);
        $search = $request->input("search", "");

        if (!is_numeric($currentPage) || $currentPage < 1) {
            $currentPage = 1;
        }

        if (
            !is_numeric($itemsPerPage) ||
            $itemsPerPage < 1 ||
            $itemsPerPage > 100
        ) {
            $itemsPerPage = 10;
        }

        $query = DB::table("game_news")
            ->select(
                "id",
                "game_id",
                "season_id",
                "round",
                "winner_id",
                "title",
                "content",
                "created_at",
                "updated_at"
            )
            ->where("season_id", $seasonId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where("title", "like", "%" . $search . "%")->orWhere(
                    "content",
                    "like",
                    "%" . $search . "%"
                );
            });
        }

        $totalItems = $query->count();
        $totalPages = ceil($totalItems / $itemsPerPage);

        $news = $query
            ->orderBy("created_at", "desc")
            ->skip(($currentPage - 1) * $itemsPerPage)
            ->take($itemsPerPage)
            ->get();

        return response()->json(
            [
                "data" => $news,
                "current_page" => (int) $currentPage,
                "total_pages" => (int) $totalPages,
                "total_items" => (int) $totalItems,
                "itemsperpage" => (int) $itemsPerPage,
            ],
            200
        );
    }

    public function createGameNewsFromGame($gameId)
    {
        /*
        |--------------------------------------------------------------------------
        | GAME INFORMATION
        |--------------------------------------------------------------------------
        */

        $game = DB::table("schedule_view as sv")
            ->select(
                "sv.game_id",
                "sv.season_id",
                "sv.is_overtime",
                "sv.round",
                "sv.home_team_name as home_team",
                "sv.home_id as home_team_id",
                "sv.away_team_name as away_team",
                "sv.away_id as away_team_id",
                "sv.winner_id",
                "sv.winning_name as winner_team",
                "sv.home_score",
                "sv.away_score",
                "sv.conference_id"
            )
            ->where("sv.id", $gameId)
            ->first();

        if (!$game) {
            return;
        }

        
        $quarterBreakdown = DB::table("game_quarter_breakdown")
            ->where("game_id", $game->game_id)
            ->where("season_id", $game->season_id)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Identify HOME / AWAY quarter rows
        |--------------------------------------------------------------------------
        */

        $homeQuarter = $quarterBreakdown->firstWhere(
            "team_id",
            $game->home_team_id
        );

        $awayQuarter = $quarterBreakdown->firstWhere(
            "team_id",
            $game->away_team_id
        );
        /*
        |--------------------------------------------------------------------------
        | Safe fallback
        |--------------------------------------------------------------------------
        */

        $homeQ1 = $homeQuarter ? (int) $homeQuarter->Q1 : 0;
        $homeQ2 = $homeQuarter ? (int) $homeQuarter->Q2 : 0;
        $homeQ3 = $homeQuarter ? (int) $homeQuarter->Q3 : 0;
        $homeQ4 = $homeQuarter ? (int) $homeQuarter->Q4 : 0;

        $awayQ1 = $awayQuarter ? (int) $awayQuarter->Q1 : 0;
        $awayQ2 = $awayQuarter ? (int) $awayQuarter->Q2 : 0;
        $awayQ3 = $awayQuarter ? (int) $awayQuarter->Q3 : 0;
        $awayQ4 = $awayQuarter ? (int) $awayQuarter->Q4 : 0;

        $homeOT1 = $homeQuarter ? (int) $homeQuarter->OT1 : 0;
        $homeOT2 = $homeQuarter ? (int) $homeQuarter->OT2 : 0;
        $homeOT3 = $homeQuarter ? (int) $homeQuarter->OT3 : 0;

        $awayOT1 = $awayQuarter ? (int) $awayQuarter->OT1 : 0;
        $awayOT2 = $awayQuarter ? (int) $awayQuarter->OT2 : 0;
        $awayOT3 = $awayQuarter ? (int) $awayQuarter->OT3 : 0;

        /*
        |--------------------------------------------------------------------------
        | Quarter totals
        |--------------------------------------------------------------------------
        */

        $quarterTotals = [
            1 => [
                "home" => $homeQ1,
                "away" => $awayQ1,
            ],
            2 => [
                "home" => $homeQ2,
                "away" => $awayQ2,
            ],
            3 => [
                "home" => $homeQ3,
                "away" => $awayQ3,
            ],
            4 => [
                "home" => $homeQ4,
                "away" => $awayQ4,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Determine quarter winners
        |--------------------------------------------------------------------------
        */

        $quarterLeaders = [];

        foreach ($quarterTotals as $quarter => $scores) {
            if ($scores["home"] > $scores["away"]) {
                $quarterLeaders[$quarter] = "home";
            } elseif ($scores["away"] > $scores["home"]) {
                $quarterLeaders[$quarter] = "away";
            } else {
                $quarterLeaders[$quarter] = "tie";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Biggest quarter
        |--------------------------------------------------------------------------
        */

        $biggestQuarter = null;
        $biggestQuarterTotal = 0;

        foreach ($quarterTotals as $quarter => $scores) {
            $combined = $scores["home"] + $scores["away"];

            if ($combined > $biggestQuarterTotal) {
                $biggestQuarterTotal = $combined;
                $biggestQuarter = $quarter;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Winner / loser quarter scores
        |--------------------------------------------------------------------------
        */

        $winnerIsHome = (int) $game->winner_id === (int) $game->home_team_id;

        $winnerQ1 = $winnerIsHome ? $homeQ1 : $awayQ1;
        $winnerQ2 = $winnerIsHome ? $homeQ2 : $awayQ2;
        $winnerQ3 = $winnerIsHome ? $homeQ3 : $awayQ3;
        $winnerQ4 = $winnerIsHome ? $homeQ4 : $awayQ4;

        $loserQ1 = $winnerIsHome ? $awayQ1 : $homeQ1;
        $loserQ2 = $winnerIsHome ? $awayQ2 : $homeQ2;
        $loserQ3 = $winnerIsHome ? $awayQ3 : $homeQ3;
        $loserQ4 = $winnerIsHome ? $awayQ4 : $homeQ4;

        /*
        |--------------------------------------------------------------------------
        | Quarter-by-quarter margins
        |--------------------------------------------------------------------------
        */

        $quarterMargins = [
            1 => $winnerQ1 - $loserQ1,
            2 => $winnerQ2 - $loserQ2,
            3 => $winnerQ3 - $loserQ3,
            4 => $winnerQ4 - $loserQ4,
        ];

        /*
        |--------------------------------------------------------------------------
        | Best quarter by winner
        |--------------------------------------------------------------------------
        */

        $bestWinnerQuarter = 1;

        foreach ($quarterMargins as $quarter => $margin) {
            if ($margin > $quarterMargins[$bestWinnerQuarter]) {
                $bestWinnerQuarter = $quarter;
            }
        }

        $bestWinnerQuarterMargin = $quarterMargins[$bestWinnerQuarter];

        /*
        |--------------------------------------------------------------------------
        | Comeback / start / finish detection
        |--------------------------------------------------------------------------
        */

        $winnerFirstHalf = $winnerQ1 + $winnerQ2;

        $loserFirstHalf = $loserQ1 + $loserQ2;

        $winnerSecondHalf = $winnerQ3 + $winnerQ4;

        $loserSecondHalf = $loserQ3 + $loserQ4;

        $winnerWonFirstQuarter = $winnerQ1 > $loserQ1;
        $winnerWonSecondQuarter = $winnerQ2 > $loserQ2;
        $winnerWonThirdQuarter = $winnerQ3 > $loserQ3;
        $winnerWonFourthQuarter = $winnerQ4 > $loserQ4;

        $winnerLostFirstQuarter = $winnerQ1 < $loserQ1;

        $winnerCameBack =
            $winnerFirstHalf < $loserFirstHalf &&
            $winnerSecondHalf > $loserSecondHalf;

        $winnerClosedStrong = $winnerQ4 > $loserQ4;

        $winnerWonAllQuarters =
            $winnerWonFirstQuarter &&
            $winnerWonSecondQuarter &&
            $winnerWonThirdQuarter &&
            $winnerWonFourthQuarter;

        /*
        |--------------------------------------------------------------------------
        | Overtime information
        |--------------------------------------------------------------------------
        */

        $overtimePeriods = [];

        if ($homeOT1 > 0 || $awayOT1 > 0) {
            $overtimePeriods[1] = [
                "home" => $homeOT1,
                "away" => $awayOT1,
            ];
        }

        if ($homeOT2 > 0 || $awayOT2 > 0) {
            $overtimePeriods[2] = [
                "home" => $homeOT2,
                "away" => $awayOT2,
            ];
        }

        if ($homeOT3 > 0 || $awayOT3 > 0) {
            $overtimePeriods[3] = [
                "home" => $homeOT3,
                "away" => $awayOT3,
            ];
        }

        $overtimeCount = count($overtimePeriods);

        /*
        |--------------------------------------------------------------------------
        | Season
        |--------------------------------------------------------------------------
        */
        
        $totalRegularGames = (int)$this->helper->totalTeamGames($game->season_id);

        $isLast3Rounds = $game->round >=  ($totalRegularGames - 3);

        /*
        |--------------------------------------------------------------------------
        | Determine loser
        |--------------------------------------------------------------------------
        */

        $loser =
            $game->winner_id === $game->home_team_id
                ? $game->away_team
                : $game->home_team;

        $loserId =
            $game->winner_id === $game->home_team_id
                ? $game->away_team_id
                : $game->home_team_id;

        $winnerScore =
            $game->winner_id === $game->home_team_id
                ? $game->home_score
                : $game->away_score;

        $loserScore =
            $game->winner_id === $game->home_team_id
                ? $game->away_score
                : $game->home_score;

        $scoreMargin = abs($winnerScore - $loserScore);

        $game->loser_id = $loserId;

        /*
        |--------------------------------------------------------------------------
        | Draft pick
        |--------------------------------------------------------------------------
        */

        $draftPick = DB::table("drafts as d")
            ->join("players as p", "d.player_id", "=", "p.id")
            ->select(
                "p.name as player_name",
                "d.round",
                "d.pick_number",
                "d.season_id as draft_season"
            )
            ->where("d.team_id", $game->winner_id)
            ->where("d.season_id", $game->season_id)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Team standings
        |--------------------------------------------------------------------------
        */

        $winnerStats = DB::table("standings_view as s")
            ->select(
                "s.wins",
                "s.losses",
                "s.home_ppg",
                "s.score_difference",
                "s.conference_rank",
                "s.overall_rank",
                "s.streak_status",
                "s.is_defending_champion",
                "s.next_opponent_name",
                "s.championships",
                "s.conference_championships",
                "s.playoff_appearances"
            )
            ->where("s.team_id", $game->winner_id)
            ->where("s.season_id", $game->season_id)
            ->first();

        $loserStats = DB::table("standings_view as s")
            ->select(
                "s.wins",
                "s.losses",
                "s.home_ppg",
                "s.score_difference",
                "s.conference_rank",
                "s.overall_rank",
                "s.streak_status",
                "s.is_defending_champion",
                "s.next_opponent_name",
                "s.championships",
                "s.conference_championships",
                "s.playoff_appearances"
            )
            ->where("s.team_id", $loserId)
            ->where("s.season_id", $game->season_id)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Top performer
        |--------------------------------------------------------------------------
        */

        $topPerformer = DB::table("player_game_stats as pgs")
            ->join("players as p", "pgs.player_id", "=", "p.id")
            ->leftJoin("drafts as d", function ($join) use ($game) {
                $join
                    ->on("p.id", "=", "d.player_id")
                    ->where("d.season_id", "=", $game->season_id);
            })
            ->select(
                "p.name as player_name",
                "pgs.points",
                "pgs.rebounds",
                "pgs.assists",
                "pgs.steals",
                "pgs.blocks",
                "d.season_id as draft_season"
            )
            ->where("pgs.game_id", $game->game_id)
            ->where("pgs.team_id", $game->winner_id)
            ->orderBy("pgs.points", "desc")
            ->orderBy("pgs.rebounds", "desc")
            ->orderBy("pgs.assists", "desc")
            ->first();

        $isRookie =
            $topPerformer && $topPerformer->draft_season == $game->season_id;

        $isGoodStatLine =
            $topPerformer &&
            ($topPerformer->points >= 20 ||
                $topPerformer->rebounds >= 10 ||
                $topPerformer->assists >= 10 ||
                ($topPerformer->points >= 15 &&
                    $topPerformer->rebounds >= 10) ||
                ($topPerformer->points >= 15 && $topPerformer->assists >= 10));

        $isRareStatLine =
            $topPerformer &&
            ($topPerformer->points >= 40 ||
                ($topPerformer->points >= 10 &&
                    $topPerformer->rebounds >= 10 &&
                    $topPerformer->assists >= 10) ||
                $topPerformer->rebounds >= 20 ||
                $topPerformer->assists >= 20 ||
                $topPerformer->steals >= 7 ||
                $topPerformer->blocks >= 7);

        /*
        |--------------------------------------------------------------------------
        | Top vs worst
        |--------------------------------------------------------------------------
        */

        $isTopVsWorst = false;
        $maxRank = null;

        if ($winnerStats && $loserStats && $game->conference_id) {
            $maxRank = DB::table("standings_view")
                ->where("conference_id", $game->conference_id)
                ->where("season_id", $game->season_id)
                ->max("conference_rank");

            $isTopVsWorst =
                ($winnerStats->conference_rank == 1 &&
                    $loserStats->conference_rank == $maxRank) ||
                ($loserStats->conference_rank == 1 &&
                    $winnerStats->conference_rank == $maxRank);
        }

        /*
        |--------------------------------------------------------------------------
        | Head-to-head
        |--------------------------------------------------------------------------
        */

        $headToHead = DB::table("head_to_head as h2h")
            ->select("h2h.win_percentage", "h2h.points_for")
            ->where("h2h.team_id", $game->winner_id)
            ->where("h2h.opponent_id", $loserId)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Playoff context
        |--------------------------------------------------------------------------
        */

        $isPlayoff = !is_numeric($game->round);

        $playoffSeries = null;

        $seriesWins = 0;
        $seriesLosses = 0;
        $raceTo = null;
        $seriesLength = null;
        $seriesStatus = null;

        $isSeriesClinched = false;
        $isSeriesTied = false;
        $isGame7 = false;
        $isEliminationGame = false;
        $isPotentialClincher = false;

        $isOvertime = (int) $game->is_overtime;

        if ($isPlayoff) {
            $playoffSeries = DB::table("playoff_series")
                ->where("season_id", $game->season_id)
                ->where("round", $game->round)
                ->where(function ($query) use ($game) {
                    $query
                        ->where(function ($q) use ($game) {
                            $q->where("home_team_id", $game->winner_id)->where(
                                "away_team_id",
                                $game->loser_id
                            );
                        })
                        ->orWhere(function ($q) use ($game) {
                            $q->where("home_team_id", $game->loser_id)->where(
                                "away_team_id",
                                $game->winner_id
                            );
                        });
                })
                ->first();

            if ($playoffSeries) {
                $raceTo = (int) $playoffSeries->race_to;
                $seriesLength = (int) $playoffSeries->series_length;
                $seriesStatus = (int) $playoffSeries->status;

                $homeWins = (int) $playoffSeries->home_wins;
                $awayWins = (int) $playoffSeries->away_wins;

                if (
                    (int) $game->winner_id ===
                    (int) $playoffSeries->home_team_id
                ) {
                    $seriesWins = $homeWins;
                    $seriesLosses = $awayWins;
                } else {
                    $seriesWins = $awayWins;
                    $seriesLosses = $homeWins;
                }

                $requiredWins = $raceTo;

                $isSeriesClinched =
                    $seriesStatus === 2 ||
                    !empty($playoffSeries->winner_team_id) ||
                    $seriesWins >= $requiredWins;

                $isSeriesTied =
                    !$isSeriesClinched && $seriesWins === $seriesLosses;

                $isGame7 =
                    !$isSeriesClinched &&
                    $seriesLength === 7 &&
                    $seriesWins === 4 &&
                    $seriesLosses === 3;

                $isEliminationGame =
                    !$isSeriesClinched && $seriesLosses === $requiredWins - 1;

                $isPotentialClincher =
                    !$isSeriesClinched && $seriesWins === $requiredWins - 1;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Team chemistry
        |--------------------------------------------------------------------------
        */

        $teamInfo = DB::table("team_season_info as tsi")
            ->select("tsi.chemistry")
            ->where("tsi.team_id", $game->winner_id)
            ->where("tsi.season_id", $game->season_id)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Injuries
        |--------------------------------------------------------------------------
        */

        $injuredPlayers = DB::table("player_game_stats as pgs")
            ->join("players as p", "pgs.player_id", "=", "p.id")
            ->select(
                "p.name as player_name",
                "p.injury_type",
                "p.injury_recovery_games",
                "pgs.team_id"
            )
            ->where("pgs.game_id", $game->game_id)
            ->where("pgs.is_injured", true)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Streaks
        |--------------------------------------------------------------------------
        */

        $winnerRank = $winnerStats ? $winnerStats->conference_rank : null;

        $loserRank = $loserStats ? $loserStats->conference_rank : null;

        $winnerOnStreak = 0;
        $winnerWasOnSkid = 0;

        if (
            $winnerStats &&
            preg_match("/W(\d+)/", $winnerStats->streak_status, $wm)
        ) {
            $winnerOnStreak = (int) $wm[1];
        }

        if (
            $winnerStats &&
            preg_match("/L(\d+)/", $winnerStats->streak_status, $lm)
        ) {
            $winnerWasOnSkid = (int) $lm[1];
        }

        $isNumberOneMatch =
            $winnerRank &&
            $loserRank &&
            (($winnerRank == 1 && $loserRank == 2) ||
                ($winnerRank == 2 && $loserRank == 1));

        /*
        |--------------------------------------------------------------------------
        | Quarter-based story flags
        |--------------------------------------------------------------------------
        */

        $quarterStoryTemplates = [];

        if ($quarterBreakdown->count() === 2) {
            /*
            |--------------------------------------------------------------------------
            | Winner won every quarter
            |--------------------------------------------------------------------------
            */

            if ($winnerWonAllQuarters) {
                $quarterStoryTemplates[] =
                    "{winner} controlled every quarter, never allowing {loser} to win a period.";

                $quarterStoryTemplates[] =
                    "The biggest difference came from consistency: {winner} won all four quarters against {loser}.";

                $quarterStoryTemplates[] =
                    "There was no single quarter that decided this one. {winner} won each period and steadily built its advantage.";
            }
            /*
            |--------------------------------------------------------------------------
            | Comeback
            |--------------------------------------------------------------------------
            */ elseif (
                $winnerCameBack
            ) {
                $quarterStoryTemplates[] = "{winner} trailed after the first half but flipped the game after halftime, outscoring {loser} {$winnerSecondHalf}-{$loserSecondHalf} in the second half.";

                $quarterStoryTemplates[] =
                    "The game changed after halftime. {winner} erased its early deficit and finished the night with control.";

                $quarterStoryTemplates[] =
                    "{loser} had the better start, but {winner} turned the momentum around after the break and completed the comeback.";
            }
            /*
            |--------------------------------------------------------------------------
            | Strong fourth quarter
            |--------------------------------------------------------------------------
            */ elseif (
                $winnerClosedStrong
            ) {
                $fourthQuarterMargin = $winnerQ4 - $loserQ4;

                if ($fourthQuarterMargin >= 6) {
                    $quarterStoryTemplates[] = "{winner} saved its best basketball for the fourth quarter, outscoring {loser} {$winnerQ4}-{$loserQ4} down the stretch.";

                    $quarterStoryTemplates[] =
                        "The decisive stretch came in the fourth, when {winner} created separation and closed the door on {loser}.";

                    $quarterStoryTemplates[] =
                        "After a competitive first three quarters, {winner} took over in the fourth and finished the job.";
                } else {
                    $quarterStoryTemplates[] = "{winner} finished stronger when it mattered, winning the fourth quarter {$winnerQ4}-{$loserQ4}.";
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Slow start
            |--------------------------------------------------------------------------
            */ elseif (
                $winnerLostFirstQuarter
            ) {
                $quarterStoryTemplates[] =
                    "{winner} did not have the better start, but the team gradually settled in and took control as the game progressed.";

                $quarterStoryTemplates[] =
                    "The opening quarter belonged to {loser}, but {winner} answered and eventually turned the game around.";
            }

            /*
            |--------------------------------------------------------------------------
            | Big quarter
            |--------------------------------------------------------------------------
            */

            if ($biggestQuarter !== null) {
                if ($biggestQuarter !== 4) {
                    $quarterStoryTemplates[] = "The game opened up in the {$this->quarterFormatter($biggestQuarter)} Quarter, which produced {$biggestQuarterTotal} combined points.";
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Winner's best quarter
            |--------------------------------------------------------------------------
            */

            if ($bestWinnerQuarterMargin >= 8) {
                $quarterStoryTemplates[] = "{winner} made its biggest push in the {$this->quarterFormatter($bestWinnerQuarter)} quarter, winning that period by {$bestWinnerQuarterMargin} points.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | OVERTIME STORY
        |--------------------------------------------------------------------------
        */

        $overtimeStoryTemplates = [];

        if ($overtimeCount > 0) {
            if ($overtimeCount === 1) {
                $overtimeStoryTemplates = [
                    "{winner} needed overtime to settle the game, outlasting {loser} after regulation.",

                    "Regulation could not separate the teams, but {winner} made the decisive plays in overtime.",

                    "The game went beyond four quarters before {winner} finally pulled away.",

                    "Four quarters were not enough. {winner} survived overtime and secured the victory.",

                    "{winner} kept its composure after regulation and finished off {loser} in overtime.",
                ];
            } elseif ($overtimeCount === 2) {
                $overtimeStoryTemplates = [
                    "One overtime was not enough. {winner} and {loser} needed two extra periods before {winner} finally secured the win.",

                    "The game turned into a marathon as two overtime periods were required to determine the winner.",

                    "After regulation failed to produce a winner, the teams battled through two overtimes before {winner} prevailed.",

                    "{winner} survived a double-overtime thriller, making the final plays necessary to put away {loser}.",
                ];
            } else {
                $overtimeStoryTemplates = [
                    "This one went the distance and then some. {winner} survived three overtimes to finally defeat {loser}.",

                    "Three overtime periods were needed before {winner} could finally separate from {loser}.",

                    "It became an instant marathon as {winner} and {loser} battled through three overtimes before {winner} prevailed.",

                    "Four quarters and three overtimes still were not enough to make this easy, but {winner} eventually found the winning edge.",

                    "A three-overtime classic ended with {winner} standing after an extraordinary battle against {loser}.",
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HEADLINES
        |--------------------------------------------------------------------------
        */

        $headlineTemplates = [];

        /*
        |--------------------------------------------------------------------------
        | PLAYOFF HEADLINES
        |--------------------------------------------------------------------------
        */

        if ($isPlayoff) {
            if ($isGame7) {
                $headlineTemplates = [
                    "{winner} Wins the Game 7 Battle, Eliminates {loser}",

                    "Game 7 Belongs to {winner} as {loser} Falls",

                    "{winner} Survives Game 7, Advances in the Playoffs",

                    "Winner Takes All: {winner} Beats {loser} in Game 7",

                    "Game 7 Glory: {winner} Ends {loser}'s Playoff Run",

                    "{winner} Delivers When It Matters Most, Wins Game 7",
                ];
            } elseif ($game->round === "finals" && $isSeriesClinched) {
                $headlineTemplates = [
                    "{winner} Wins It All, Finishes Off {loser} in The Finals",

                    "{winner} Are Champions: {loser} Falls in The Finals",

                    "The Crown Belongs to {winner}",

                    "{winner} Clinch the Championship Against {loser}",

                    "Championship Secured: {winner} Finish the Job",
                ];
            } elseif ($isSeriesClinched) {
                $headlineTemplates = [
                    "{winner} Closes Out {loser}, Advances From {round}",

                    "{winner} Finishes the Job, Eliminates {loser}",

                    "Series Over: {winner} Knocks Out {loser} in {round}",

                    "{winner} Punches Its Ticket to the Next Round",

                    "{winner} Completes the Series Win Over {loser}",
                ];
            } elseif ($isPotentialClincher) {
                $headlineTemplates = [
                    "{winner} Moves Within One Win of Eliminating {loser}",

                    "{winner} Takes Command of {round} Series Against {loser}",

                    "{winner} Puts {loser} on the Brink of Elimination",

                    "One Win Away: {winner} Takes {$seriesWins}-{$seriesLosses} Series Lead",

                    "{winner} Takes a Huge Step Toward Advancing",
                ];
            } elseif ($isSeriesTied) {
                $headlineTemplates = [
                    "{winner} Breaks {round} Series Tie With Win Over {loser}",

                    "Series Level No More: {winner} Takes the Lead Against {loser}",

                    "{winner} Grabs Control After {round} Series Deadlock",

                    "{winner} Takes {$seriesWins}-{$seriesLosses} Series Lead Over {loser}",
                ];
            } else {
                $headlineTemplates = [
                    "{winner} Fights Back, Cuts Into {loser}'s Series Lead",

                    "{winner} Keeps Its Playoff Run Alive Against {loser}",

                    "{winner} Answers the Call With Crucial {round} Win",

                    "{winner} Refuses to Go Quietly, Beats {loser}",

                    "{winner} Keeps the Series From Slipping Away",
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | OVERTIME PLAYOFF HEADLINES
            |--------------------------------------------------------------------------
            */

            if ($overtimeCount > 0) {
                if ($overtimeCount === 1) {
                    $headlineTemplates = [
                        "{winner} Survives Overtime, Beats {loser} in the Playoffs",

                        "Overtime Drama: {winner} Takes Down {loser}",

                        "{winner} Outlasts {loser} in Overtime Thriller",

                        "Extra Time, Extra Pressure: {winner} Beats {loser}",

                        "{winner} Wins a Postseason Overtime Battle",
                    ];
                } elseif ($overtimeCount === 2) {
                    $headlineTemplates = [
                        "Double Overtime Drama: {winner} Survives {loser}",

                        "{winner} Wins a Double-Overtime Playoff Thriller",

                        "Two Overtimes, One Winner: {winner} Beats {loser}",

                        "{winner} Outlasts {loser} After Two Overtimes",
                    ];
                } else {
                    $headlineTemplates = [
                        "Three-Overtime Classic: {winner} Finally Beats {loser}",

                        "{winner} Survives a Three-Overtime Playoff Marathon",

                        "Three Overtimes, One Winner: {winner} Takes Down {loser}",

                        "{winner} Wins an Instant Playoff Classic After Three Overtimes",
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Finals always get championship language
            |--------------------------------------------------------------------------
            */

            if ($game->round === "finals") {
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
                    ];
                } elseif ($isPotentialClincher) {
                    $headlineTemplates = [
                        "{winner} One Win From the Championship After Beating {loser}",

                        "{winner} Take Control of The Finals Against {loser}",

                        "Championship Within Reach as {winner} Beat {loser}",

                        "{loser} on the Brink as {winner} Take Finals Lead",
                    ];
                }
            }
        } else {
            /*
            |--------------------------------------------------------------------------
            | REGULAR SEASON HEADLINES
            |--------------------------------------------------------------------------
            */

            if ($overtimeCount > 0) {
                if ($overtimeCount === 1) {
                    $headlineTemplates = [
                        "{winner} Survives Overtime, Beats {loser} {home_score}-{away_score}",

                        "Overtime Thriller: {winner} Edges {loser}",

                        "{winner} Outlasts {loser} After Four Quarters Aren't Enough",

                        "Extra Time Decides It: {winner} Beats {loser}",

                        "{winner} Finds a Way in Overtime Against {loser}",
                    ];
                } elseif ($overtimeCount === 2) {
                    $headlineTemplates = [
                        "Double Overtime Thriller: {winner} Survives {loser}",

                        "{winner} Wins a Double-Overtime Classic Against {loser}",

                        "Two Overtimes, One Winner: {winner} Beats {loser}",

                        "{winner} Outlasts {loser} After Two Overtimes",

                        "Double-Overtime Drama Ends With {winner} on Top",
                    ];
                } else {
                    $headlineTemplates = [
                        "Three-Overtime Classic: {winner} Finally Beats {loser}",

                        "{winner} Survives a Three-Overtime Marathon Against {loser}",

                        "Three Overtimes, One Winner: {winner} Takes Down {loser}",

                        "{winner} Wins an Instant Classic After Three Overtimes",

                        "Three Overtimes Later, {winner} Finally Breaks Through",
                    ];
                }
            } elseif ($winnerOnStreak >= 3) {
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
        }

        /*
        |--------------------------------------------------------------------------
        | Narrative starters
        |--------------------------------------------------------------------------
        */

        $contentStarters = [];
        $contentMiddles = [];
        $contentEnders = [];

        $roundLabel = $this->helper->roundFormatter($game->round);

        $winnerRecord = $winnerStats
            ? "{$winnerStats->wins}-{$winnerStats->losses}"
            : "their current record";

        $loserRecord = $loserStats
            ? "{$loserStats->wins}-{$loserStats->losses}"
            : "their current record";

        /*
        |--------------------------------------------------------------------------
        | Playoff narrative
        |--------------------------------------------------------------------------
        */

        if ($isPlayoff) {
            if ($isGame7 && $topPerformer) {
                $contentStarters = [
                    "Seven games came down to one night, and {winner} delivered when the season was on the line. {player} led the way with {points} points, {rebounds} rebounds and {assists} assists.",

                    "Everything was on the line in Game 7, and {winner} found a way to survive {loser}. {player} stepped into the pressure with {points} points and {assists} assists.",

                    "There was no tomorrow for either team, and {winner} handled the moment. {player} finished with {points} points, {rebounds} rebounds and {assists} assists in the deciding game.",
                ];
            } elseif ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "The postseason spotlight found rookie {player}, who delivered {points} points, {rebounds} rebounds and {assists} assists as {winner} beat {loser} {home_score}-{away_score}.",

                    "Rookie {player} looked comfortable under playoff pressure, producing {points} points and {assists} assists to help {winner} take down {loser}.",

                    "First-year standout {player} turned in a playoff performance well beyond his experience, leading {winner} with {points} points against {loser}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "Playoff basketball demands stars, and {player} delivered. The standout posted {points} points, {rebounds} rebounds and {assists} assists to lead {winner} past {loser}.",

                    "{player} authored the defining performance of the night, pouring in {points} points as {winner} took another step forward in {round}.",

                    "With the pressure turned all the way up, {player} responded with {points} points, {rebounds} rebounds and {assists} assists for {winner}.",
                ];
            } elseif ($scoreMargin <= 3) {
                $contentStarters = [
                    "This was playoff basketball in its purest form: tight, physical and decided by the smallest details. {winner} escaped {loser} {home_score}-{away_score}.",

                    "There was nowhere to hide in this one. {winner} and {loser} traded blows throughout before {winner} finally secured the narrow victory.",

                    "The pressure was obvious from the opening tip, and neither team could create separation. {winner} made the final plays count.",
                ];
            } else {
                $contentStarters = [
                    "{winner} took control of the postseason matchup early and never surrendered it, defeating {loser} {home_score}-{away_score}.",

                    "The playoff intensity was there, but so was the control from {winner}, which steadily pulled away from {loser}.",

                    "{winner} delivered a composed postseason performance, beating {loser} and putting another important win on the playoff ledger.",
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Playoff middle
            |--------------------------------------------------------------------------
            */

            if ($isGame7) {
                $contentMiddles = [
                    "The series could not have gone further, and {winner} made the decisive plays in the winner-take-all finale.",

                    "After six games of back-and-forth basketball, the series finally had its answer: {winner} advances, while {loser} goes home.",

                    "The pressure was as high as it gets, with both teams entering the night one win from the next round.",
                ];
            } elseif ($isSeriesClinched) {
                $contentMiddles = [
                    "The victory closes the series and sends {winner} through, while {loser}'s postseason comes to an end.",

                    "There is no next game for {loser}; {winner} has officially won the series and advances.",

                    "{winner} handled the pressure of closing the door, ending the series when the opportunity arrived.",
                ];
            } elseif ($isPotentialClincher) {
                $contentMiddles = [
                    "{winner} now holds the leverage, sitting one win away from advancing while {loser} faces a must-win situation.",

                    "The pressure has shifted squarely onto {loser}, which now needs a response to keep its postseason alive.",

                    "{winner} has put itself in position to finish the series, but the job is not complete yet.",
                ];
            } elseif ($seriesWins > $seriesLosses) {
                $contentMiddles = [
                    "{winner} continues to hold the series advantage, forcing {loser} to find answers before the next game.",

                    "The series is moving in {winner}'s direction, but the margin for error remains thin in a playoff setting.",

                    "{winner} has built valuable momentum, while {loser} now has to respond before the deficit grows.",
                ];
            } else {
                $contentMiddles = [
                    "The result keeps {winner}'s postseason hopes alive and prevents {loser} from putting the series away.",

                    "{winner} needed a response, and tonight provided it. The pressure remains, but the series is still alive.",

                    "For {winner}, this was about survival as much as victory. The team now gets another chance to extend its run.",
                ];
            }

            $contentEnders = [
                "The series moves on, and the margin for error gets smaller with every game.",

                "There is no time to dwell on the result. Both teams now turn toward the next playoff battle.",

                "The postseason waits for no one. The next game will bring another test and another chance to take control.",
            ];

            if ($game->round === "finals") {
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
                    ];
                } else {
                    $contentEnders = [
                        "The Finals continue, and the championship race is far from settled.",

                        "The next Finals game now carries even greater weight as both teams chase the crown.",

                        "The title remains within reach, but the next chapter promises another battle.",
                    ];
                }
            }
        } else {
            /*
            |--------------------------------------------------------------------------
            | Regular season starters
            |--------------------------------------------------------------------------
            */

            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie {player} stole the spotlight in {round}, finishing with {points} points, {rebounds} rebounds and {assists} assists as {winner} beat {loser}.",

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

            /*
            |--------------------------------------------------------------------------
            | Regular season middle
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Regular season endings
            |--------------------------------------------------------------------------
            */

            $contentEnders = [
                "{winner} will now look to build on the result as the regular-season schedule continues.",

                "There is still plenty of basketball left, but {winner} can bank this one and move on.",

                "It is only one game, but it is one {winner} will be happy to have in the standings.",

                "For now, the story belongs to {winner}; the season still has plenty of chapters left to write.",
            ];

            if ($winnerStats && $winnerStats->conference_rank == 1) {
                $contentEnders[] =
                    "{winner} remains at the top of the conference and will now try to hold that position.";
            } elseif ($winnerStats && $winnerStats->conference_rank <= 6) {
                $contentEnders[] =
                    "The result keeps {winner} in strong playoff position and gives the team another opportunity to climb.";
            }

            if ($winnerStats && $winnerStats->playoff_appearances > 5) {
                $contentEnders[] =
                    "The franchise's postseason experience showed in the way {winner} managed the game.";
            }

            if ($winnerStats && $winnerStats->championships > 0) {
                $contentEnders[] =
                    "With championship banners already in its history, {winner} knows regular-season wins serve a bigger goal.";
            }
        }

        if ($winnerStats && $winnerStats->championships > 0) {
                $contentEnders[] =
                    "With championship banners already in its history, {winner} knows regular-season wins serve a bigger goal.";
        }

        /*
        |--------------------------------------------------------------------------
        | Add quarter breakdown to article middle
        |--------------------------------------------------------------------------
        */
        $quarterStory = '';
        if ($quarterStoryTemplates) {
            /*
            | Shuffle so the quarter information does not always appear
            | in exactly the same sentence.
            */

            shuffle($quarterStoryTemplates);

            $quarterStory = $quarterStoryTemplates[0];

        }

        /*
        |--------------------------------------------------------------------------
        | Add overtime narrative
        |--------------------------------------------------------------------------
        */
        $overtimeStory = '';

        if ($overtimeStoryTemplates) {
            shuffle($overtimeStoryTemplates);

            $overtimeStory = $overtimeStoryTemplates[0];
        }

        /*
        |--------------------------------------------------------------------------
        | Placeholder replacement
        |--------------------------------------------------------------------------
        */

        $replace = function ($template) use (
            $game,
            $loser,
            $topPerformer,
            $winnerStats,
            $headToHead,
            $teamInfo,
            $draftPick,
            $roundLabel,
            $winnerQ1,
            $winnerQ2,
            $winnerQ3,
            $winnerQ4,
            $loserQ1,
            $loserQ2,
            $loserQ3,
            $loserQ4,
            $homeQ1,
            $homeQ2,
            $homeQ3,
            $homeQ4,
            $awayQ1,
            $awayQ2,
            $awayQ3,
            $awayQ4,
            $homeOT1,
            $homeOT2,
            $homeOT3,
            $awayOT1,
            $awayOT2,
            $awayOT3,
            $overtimeCount,
            $biggestQuarter,
            $biggestQuarterTotal,
            $bestWinnerQuarter,
            $bestWinnerQuarterMargin
        ) {
            return str_replace(
                [
                    "{round}",
                    "{winner}",
                    "{loser}",
                    "{home_score}",
                    "{away_score}",

                    "{player}",
                    "{points}",
                    "{rebounds}",
                    "{assists}",

                    "{ppg}",
                    "{win_percentage}",
                    "{chemistry}",
                    "{score_difference}",

                    "{wins}",
                    "{losses}",
                    "{conference_rank}",

                    "{winner_q1}",
                    "{winner_q2}",
                    "{winner_q3}",
                    "{winner_q4}",

                    "{loser_q1}",
                    "{loser_q2}",
                    "{loser_q3}",
                    "{loser_q4}",

                    "{home_q1}",
                    "{home_q2}",
                    "{home_q3}",
                    "{home_q4}",

                    "{away_q1}",
                    "{away_q2}",
                    "{away_q3}",
                    "{away_q4}",

                    "{home_ot1}",
                    "{home_ot2}",
                    "{home_ot3}",

                    "{away_ot1}",
                    "{away_ot2}",
                    "{away_ot3}",

                    "{overtime_count}",
                    "{biggest_quarter}",
                    "{biggest_quarter_total}",
                    "{best_winner_quarter}",
                    "{best_winner_quarter_margin}",
                ],

                [
                    $roundLabel,

                    $game->winner_team,

                    $loser,

                    $game->home_score,

                    $game->away_score,

                    $topPerformer
                        ? $topPerformer->player_name
                        : ($draftPick
                            ? $draftPick->player_name
                            : "the standout performer"),

                    $topPerformer ? $topPerformer->points : "N/A",

                    $topPerformer ? $topPerformer->rebounds : "N/A",

                    $topPerformer ? $topPerformer->assists : "N/A",

                    $winnerStats
                        ? number_format($winnerStats->home_ppg, 1)
                        : "N/A",

                    $headToHead
                        ? number_format($headToHead->win_percentage * 100, 1)
                        : "N/A",

                    $teamInfo ? $teamInfo->chemistry : "N/A",

                    $winnerStats ? $winnerStats->score_difference : "N/A",

                    $winnerStats ? $winnerStats->wins : "N/A",

                    $winnerStats ? $winnerStats->losses : "N/A",

                    $winnerStats ? $winnerStats->conference_rank : "N/A",

                    $winnerQ1,
                    $winnerQ2,
                    $winnerQ3,
                    $winnerQ4,

                    $loserQ1,
                    $loserQ2,
                    $loserQ3,
                    $loserQ4,

                    $homeQ1,
                    $homeQ2,
                    $homeQ3,
                    $homeQ4,

                    $awayQ1,
                    $awayQ2,
                    $awayQ3,
                    $awayQ4,

                    $homeOT1,
                    $homeOT2,
                    $homeOT3,

                    $awayOT1,
                    $awayOT2,
                    $awayOT3,

                    $overtimeCount,

                    $biggestQuarter ?? "N/A",

                    $biggestQuarterTotal,

                    $bestWinnerQuarter,

                    $bestWinnerQuarterMargin,
                ],

                $template
            );
        };

        /*
        |--------------------------------------------------------------------------
        | Playoff placeholders
        |--------------------------------------------------------------------------
        */

        $replacePlayoff = function ($template) use (
            $replace,
            $seriesWins,
            $seriesLosses,
            $raceTo,
            $seriesLength
        ) {
            return str_replace(
                [
                    "{series_wins}",
                    "{series_losses}",
                    "{race_to}",
                    "{series_length}",
                ],

                [
                    $seriesWins,
                    $seriesLosses,
                    $raceTo ?? "N/A",
                    $seriesLength ?? "N/A",
                ],

                $replace($template)
            );
        };

        /*
        |--------------------------------------------------------------------------
        | Generate title
        |--------------------------------------------------------------------------
        */

        if (empty($headlineTemplates)) {
            $headlineTemplates = [
                "{winner} Defeats {loser} {home_score}-{away_score}",
            ];
        }

        $title = $isPlayoff
            ? $replacePlayoff(
                $headlineTemplates[array_rand($headlineTemplates)]
            )
            : $replace($headlineTemplates[array_rand($headlineTemplates)]);

        /*
        |--------------------------------------------------------------------------
        | Generate article
        |--------------------------------------------------------------------------
        */
        // $quarterStory $overtimeStory
        $content = $isPlayoff
            ? $replacePlayoff($contentStarters[array_rand($contentStarters)]) .
                " " .
                $replacePlayoff($contentMiddles[array_rand($contentMiddles)]) .
                " " .
                $replacePlayoff($quarterStory) .
                " " .
                $replacePlayoff($overtimeStory) .
                " " .
                $replacePlayoff($contentEnders[array_rand($contentEnders)])
            : $replace($contentStarters[array_rand($contentStarters)]) .
                " " .
                $replace($contentMiddles[array_rand($contentMiddles)]) .
                " " .
                $replace($quarterStory) .
                " " .
                $replace($overtimeStory) .
                " " .
                $replace($contentEnders[array_rand($contentEnders)]);

        /*
        |--------------------------------------------------------------------------
        | Injury content
        |--------------------------------------------------------------------------
        */

        $injuryContent = "";

        $winnerInjured = $injuredPlayers->where("team_id", $game->winner_id);

        $loserInjured = $injuredPlayers->where("team_id", $loserId);

        if ($winnerInjured->count() > 0) {
            $winnerInjuryList = $winnerInjured
                ->map(function ($player) {
                    $injuryType = $player->injury_type
                        ? ucwords(str_replace("_", " ", $player->injury_type))
                        : "an undisclosed issue";

                    $recoveryStatus =
                        $player->injury_recovery_games == 0 ||
                        is_null($player->injury_type)
                            ? "is expected to return next game"
                            : "is expected to miss " .
                                $player->injury_recovery_games .
                                " games";

                    return $player->player_name .
                        " (" .
                        $injuryType .
                        ", " .
                        $recoveryStatus .
                        ")";
                })
                ->implode(", ");

            $injuryContent .= " The win came with an added challenge, as {$game->winner_team} played through injuries to {$winnerInjuryList}.";
        }

        if ($loserInjured->count() > 0) {
            $loserInjuryList = $loserInjured
                ->map(function ($player) {
                    $injuryType = $player->injury_type
                        ? ucwords(str_replace("_", " ", $player->injury_type))
                        : "an undisclosed issue";

                    $recoveryStatus =
                        $player->injury_recovery_games == 0 ||
                        is_null($player->injury_type)
                            ? "is expected to return next game"
                            : "is expected to miss " .
                                $player->injury_recovery_games .
                                " games";

                    return $player->player_name .
                        " (" .
                        $injuryType .
                        ", " .
                        $recoveryStatus .
                        ")";
                })
                ->implode(", ");

            $injuryContent .= " On the other side, {$loser} was dealing with injuries to {$loserInjuryList}.";
        }

        $content .= $injuryContent;

        /*
        |--------------------------------------------------------------------------
        | Save news
        |--------------------------------------------------------------------------
        */

        DB::table("game_news")->insert([
            "game_id" => $game->game_id,

            "season_id" => $game->season_id,

            "round" => $game->round,

            "winner_id" => $game->winner_id,

            "title" => $title,

            "content" => $content,

            "created_at" => now(),

            "updated_at" => now(),
        ]);
    }

    private function quarterFormatter($quarter){
        switch ($quarter) {
            case 1:
                return '1st';
                break;
            case 2:
                return '2nd';
                break;
            case 3:
                return '3rd';
                break;
            case 3:
                return '4th';
                break;
            default:
                return '-';
                break;
        }
    }
}
