<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
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
        $currentPage = $request->input('current_page', 1);
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
            ->select('id', 'game_id', 'season_id', 'round', 'winner_id', 'title', 'content', 'created_at', 'updated_at');

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
            'pagination' => [
                'current_page' => (int) $currentPage,
                'total_pages' => (int) $totalPages,
                'total_items' => (int) $totalItems,
                'itemsperpage' => (int) $itemsPerPage,
            ],
        ], 200);
    }

    private function createGameNewsFromGame($gameId)
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

        // Handle tie case (no winner_id)
        if (!$game->winner_id) {
            $title = "{$game->home_team} and {$game->away_team} Battle to a {$game->home_score}-{$game->away_score} Draw in Round {$game->round}";
            $content = "In a thrilling Round {$game->round} showdown, {$game->home_team} and {$game->away_team} fought to a hard-earned {$game->home_score}-{$game->away_score} tie. Both teams showcased relentless determination, with neither side giving an inch in a match filled with heart-stopping moments. This deadlock keeps both squads hungry for their next chance to claim victory.";

            DB::table('game_news')->insert([
                'game_id'    => $game->game_id,
                'season_id'  => $game->season_id,
                'round'      => $game->round,
                'winner_id'  => null,
                'title'      => $title,
                'content'    => $content,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        // Determine loser and scores
        $loser = $game->winner_id === $game->home_team_id ? $game->away_team : $game->home_team;
        $loserId = $game->winner_id === $game->home_team_id ? $game->away_team_id : $game->home_team_id;
        $winnerScore = $game->winner_id === $game->home_team_id ? $game->home_score : $game->away_score;
        $loserScore = $game->winner_id === $game->home_team_id ? $game->away_score : $game->home_score;
        $scoreMargin = abs($winnerScore - $loserScore);

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

        // Random headline templates
        $headlineTemplates = [];
        if ($winnerStats && preg_match('/W5/', $winnerStats->streak_status)) {
            $headlineTemplates = [
                "{winner} Stuns {loser} {home_score}-{away_score} in Round {round} Thriller, Extends W5 Streak",
                "{winner} Dominates {loser} {home_score}-{away_score} in Round {round} Showdown, Keeps W5 Streak",
            ];
        } elseif ($winnerStats && preg_match('/L5/', $winnerStats->streak_status)) {
            $headlineTemplates = [
                "{winner} Stuns {loser} {home_score}-{away_score} in Round {round} Thriller, Snaps L5 Skid",
                "{winner} Edges Out {loser} {home_score}-{away_score} in Round {round}, Ends L5 Streak",
            ];
        } elseif ($isTopVsWorst) {
            $headlineTemplates = [
                "Conference #1 {winner} Stuns {loser} {home_score}-{away_score} in Round {round} Thriller",
                "{winner} (#1) Dominates Worst-Ranked {loser} {home_score}-{away_score} in Round {round} Showdown",
            ];
        } else {
            // Check if the match impacts the #1 conference spot
            $isNumberOneMatch = ($winnerStats->conference_rank == 1 && $loserStats->conference_rank == 2) || ($winnerStats->conference_rank == 2 && $loserStats->conference_rank == 1);

            if ($winnerStats->is_defending_champion) {
                if ($isNumberOneMatch) {
                    $headlineTemplates = [
                        "Defending Champs {winner} Hold Onto #1 Spot by Edging {loser} {home_score}-{away_score}",
                        "{winner} Secures Top Conference Position with {home_score}-{away_score} Win Over {loser}",
                        "Champion {winner} Maintains #1 Rank After Narrow Victory Against {loser} {home_score}-{away_score}",
                    ];
                } else if ($scoreMargin <= 3) {
                    // Close match, defending champion wins
                    $headlineTemplates = [
                        "Defending Champs {winner} Edge Out {loser} {home_score}-{away_score} in Round {round} Thriller",
                        "Reigning Champs {winner} Squeak Past {loser} {home_score}-{away_score} in Tight Round {round}",
                        "Champion {winner} Survives {loser} {home_score}-{away_score} in Nail-Biting Round {round}",
                        "Defending Champs {winner} Hold Off {loser} {home_score}-{away_score} in Close Round {round}",
                    ];
                } elseif ($scoreMargin <= 7) {
                    // Moderate win, defending champion wins
                    $headlineTemplates = [
                        "Defending Champs {winner} Defeat {loser} {home_score}-{away_score} in Round {round} Victory",
                        "Reigning Champs {winner} Outplay {loser} {home_score}-{away_score} in Round {round} Showdown",
                        "Champion {winner} Secures {home_score}-{away_score} Win Over {loser} in Round {round}",
                        "Defending Champs {winner} Prevail Over {loser} {home_score}-{away_score} in Round {round}",
                    ];
                } else {
                    // Dominant win, defending champion wins
                    $headlineTemplates = [
                        "Defending Champs {winner} Crush {loser} {home_score}-{away_score} in Round {round} Rout",
                        "Reigning Champs {winner} Overwhelm {loser} {home_score}-{away_score} in Round {round} Triumph",
                        "Champion {winner} Demolish {loser} {home_score}-{away_score} in Round {round} Blowout",
                        "Defending Champs {winner} Annihilate {loser} {home_score}-{away_score} in Round {round}",
                    ];
                }
            } elseif ($loserStats->is_defending_champion) {
                if ($isNumberOneMatch) {
                    $headlineTemplates = [
                        "{winner} Shakes Up #1 Spot by Defeating Defending Champs {loser} {home_score}-{away_score}",
                        "Upset Alert: {winner} Tops Defending Champion {loser} {home_score}-{away_score} in Round {round}",
                    ];
                } elseif ($scoreMargin <= 3) {
                    // Close match, defending champion loses
                    $headlineTemplates = [
                        "{winner} Stuns Defending Champs {loser} {home_score}-{away_score} in Round {round} Thriller",
                        "{winner} Upsets Reigning Champs {loser} {home_score}-{away_score} in Tight Round {round}",
                        "{winner} Shocks Champion {loser} {home_score}-{away_score} in Nail-Biting Round {round}",
                        "{winner} Edges Out Defending Champs {loser} {home_score}-{away_score} in Close Round {round}",
                    ];
                } elseif ($scoreMargin <= 7) {
                    // Moderate win, defending champion loses
                    $headlineTemplates = [
                        "{winner} Defeats Reigning Champs {loser} {home_score}-{away_score} in Round {round} Upset",
                        "{winner} Outplays Defending Champs {loser} {home_score}-{away_score} in Round {round} Showdown",
                        "{winner} Secures {home_score}-{away_score} Win Over Champion {loser} in Round {round}",
                        "{winner} Overcomes Defending Champs {loser} {home_score}-{away_score} in Round {round}",
                    ];
                } else {
                    // Dominant win, defending champion loses
                    $headlineTemplates = [
                        "{winner} Crushes Defending Champs {loser} {home_score}-{away_score} in Round {round} Shocker",
                        "{winner} Overwhelms Reigning Champs {loser} {home_score}-{away_score} in Round {round} Rout",
                        "{winner} Demolishes Champion {loser} {home_score}-{away_score} in Round {round} Blowout",
                        "{winner} Annihilates Defending Champs {loser} {home_score}-{away_score} in Round {round} Upset",
                    ];
                }
            } else {
                if ($isNumberOneMatch) {
                    $headlineTemplates = [
                        "{winner} Battles {loser} for #1 Conference Spot, Wins {home_score}-{away_score}",
                        "{winner} Climbs to Top Conference Rank with {home_score}-{away_score} Win Over {loser}",
                    ];
                } elseif ($scoreMargin <= 3) {
                    // Close match, no defending champion
                    $headlineTemplates = [
                        "{winner} Edges Out {loser} {home_score}-{away_score} in Nail-Biting Round {round}",
                        "{winner} Squeaks Past {loser} {home_score}-{away_score} in Tight Round {round} Finish",
                        "{winner} Holds Off {loser} {home_score}-{away_score} in Thrilling Round {round}",
                        "{winner} Survives {loser} {home_score}-{away_score} in Close Round {round} Battle",
                    ];
                } elseif ($scoreMargin <= 7) {
                    // Moderate win, no defending champion
                    $headlineTemplates = [
                        "{winner} Defeats {loser} {home_score}-{away_score} in Solid Round {round} Victory",
                        "{winner} Outplays {loser} {home_score}-{away_score} in Round {round} Showdown",
                        "{winner} Secures {home_score}-{away_score} Win Over {loser} in Round {round}",
                        "{winner} Prevails Over {loser} {home_score}-{away_score} in Round {round} Clash",
                    ];
                } else {
                    // Dominant win, no defending champion
                    $headlineTemplates = [
                        "{winner} Crushes {loser} {home_score}-{away_score} in Round {round} Rout",
                        "{winner} Overwhelms {loser} {home_score}-{away_score} in Dominant Round {round}",
                        "{winner} Demolishes {loser} {home_score}-{away_score} in Round {round} Blowout",
                        "{winner} Annihilates {loser} {home_score}-{away_score} in Round {round} Triumph",
                    ];
                }
            }
        }

        // Conditional content starters
        $contentStarters = [];
        if ($scoreMargin > 15) {
            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie sensation {player} erupted for {points} points, {rebounds} rebounds, and {assists} assists, leading {winner} to a {home_score}-{away_score} rout of {loser} in Round {round}.",
                    "First-year star {player}’s {points}-point, {rebounds}-rebound performance powered {winner} to a {home_score}-{away_score} blowout over {loser} in Round {round}.",
                    "Rookie {player} dazzled with {points} points and {assists} assists, fueling {winner}’s {home_score}-{away_score} domination of {loser} in Round {round}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "Led by {player}’s {points} points, {rebounds} rebounds, and {assists} assists, {winner} obliterated {loser} {home_score}-{away_score} in a Round {round} beatdown.",
                    "{player}’s {points}-point explosion fueled {winner}’s {home_score}-{away_score} rout of {loser} in Round {round}.",
                    "With {player} dropping {points} points and {assists} assists, {winner} dominated {loser} {home_score}-{away_score} in Round {round}.",
                ];
            } else {
                $contentStarters = [
                    "In a Round {round} beatdown, {winner} obliterated {loser} with a commanding {home_score}-{away_score} scoreline.",
                    "{winner} unleashed a Round {round} onslaught, crushing {loser} {home_score}-{away_score} in a one-sided affair.",
                    "Round {round} saw {winner} dominate {loser} {home_score}-{away_score}, leaving no doubt about their superiority.",
                ];
            }
        } elseif ($scoreMargin <= 5) {
            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie {player}’s {points} points, {rebounds} rebounds, and {assists} assists lifted {winner} to a thrilling {home_score}-{away_score} win over {loser} in Round {round}.",
                    "First-year standout {player} delivered {points} points and {assists} assists, guiding {winner} to a {home_score}-{away_score} nail-biter over {loser} in Round {round}.",
                    "Rookie sensation {player} shone with {points} points and {rebounds} rebounds, pushing {winner} past {loser} {home_score}-{away_score} in Round {round}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "{player}’s {points} points, {rebounds} rebounds, and {assists} assists propelled {winner} to a thrilling {home_score}-{away_score} win over {loser} in Round {round}.",
                    "In a heart-stopping Round {round} clash, {winner}, led by {player}’s {points} points and {assists} assists, edged out {loser} {home_score}-{away_score}.",
                    "{player}’s {points}-point, {rebounds}-rebound performance powered {winner} past {loser} {home_score}-{away_score} in Round {round}.",
                ];
            } else {
                $contentStarters = [
                    "In a heart-stopping Round {round} clash, {winner} edged out {loser} {home_score}-{away_score} in a nail-biting finish.",
                    "{winner} survived a Round {round} thriller, squeaking past {loser} {home_score}-{away_score} in a photo finish.",
                    "Round {round} delivered drama as {winner} narrowly defeated {loser} {home_score}-{away_score} in a tense battle.",
                ];
            }
        } else {
            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie {player} led with {points} points, {rebounds} rebounds, and {assists} assists, driving {winner} to a {home_score}-{away_score} victory over {loser} in Round {round}.",
                    "First-year star {player}’s {points} points and {assists} assists sparked {winner} to a {home_score}-{away_score} win over {loser} in Round {round}.",
                    "Rookie sensation {player} delivered {points} points and {rebounds} rebounds, powering {winner} to a {home_score}-{away_score} triumph over {loser} in Round {round}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "{player}’s {points} points, {rebounds} rebounds, and {assists} assists led {winner} to a {home_score}-{away_score} victory over {loser} in Round {round}.",
                    "With {player} posting {points} points and {assists} assists, {winner} secured a {home_score}-{away_score} win over {loser} in Round {round}.",
                    "{player}’s {points}-point, {rebounds}-rebound effort powered {winner} to a {home_score}-{away_score} triumph over {loser} in Round {round}.",
                ];
            } else {
                $contentStarters = [
                    "In an electrifying Round {round} matchup, {winner} showcased their prowess, securing a {home_score}-{away_score} win over {loser}.",
                    "Round {round} delivered a spectacle as {winner} clinched a {home_score}-{away_score} victory against {loser}.",
                    "Fans were treated to a Round {round} thriller as {winner} powered past {loser} with a {home_score}-{away_score} scoreline.",
                ];
            }
        }

        // Conditional content middles, prioritizing streak_status
        $contentMiddles = [];
        if ($winnerStats && preg_match('/W(\d+)/', $winnerStats->streak_status, $matches) && $matches[1] >= 3) {
            $streakCount = $matches[1];
            $contentMiddles = [
                "{winner} extended their {$streakCount}-game winning streak, overpowering {loser} with relentless precision.",
                "Riding a {$streakCount}-game win streak, {winner} showcased their unstoppable form against {loser}.",
            ];
        } elseif ($winnerStats && preg_match('/L(\d+)/', $winnerStats->streak_status, $matches) && $matches[1] >= 3) {
            $streakCount = $matches[1];
            $contentMiddles = [
                "Snapping a {$streakCount}-game losing streak, {winner} roared back to form with a commanding performance against {loser}.",
                "{winner} ended a {$streakCount}-game skid, outmuscling {loser} in a much-needed victory.",
            ];
        } elseif ($winnerStats && $winnerStats->score_difference > 50) {
            $contentMiddles = [
                "With a {score_difference}-point score differential this season, {winner}’s dominance was on full display, overwhelming {loser}’s defense.",
                "{winner}’s season-long {score_difference}-point edge proved too much for {loser}, who struggled to keep pace.",
            ];
        } elseif ($headToHead && $headToHead->win_percentage > 0.6) {
            $contentMiddles = [
                "Boasting a {win_percentage}% win rate against {loser} in head-to-head matchups, {winner} capitalized on their historical edge.",
                "{winner} continued their {win_percentage}% head-to-head dominance over {loser}, dictating the game’s tempo.",
            ];
        } elseif ($teamInfo && $teamInfo->chemistry > 80) {
            $contentMiddles = [
                "{winner}’s team chemistry, rated at {chemistry}, fueled seamless coordination that left {loser} scrambling.",
                "With {chemistry} chemistry, {winner} executed with precision, outmaneuvering {loser} at every turn.",
            ];
        } else {
            if ($isLast3Rounds) {
                // Upset scenario: lower-ranked team beats a higher-ranked team
                if ($winnerStats->conference_rank > $loserStats->conference_rank) {
                    $contentMiddles[] = "In a stunning upset, #{$winnerStats->conference_rank} {$game->winner_team} toppled #{$loserStats->conference_rank} {$loser} in Round {$game->round}, shaking up the playoff picture!";
                }

                // Winner beats a defending champion
                if ($loserStats->is_defending_champion) {
                    $contentMiddles[] = "{$game->winner_team} pulled off a monumental win over the defending champions, {$loser}, potentially altering postseason hopes!";
                }

                // Winner is defending champion themselves
                if ($winnerStats->is_defending_champion) {
                    $contentMiddles[] = "{$game->winner_team}, the defending champions, showed they can still dominate, holding a #{$winnerStats->conference_rank} conference rank.";
                }

                // Playoff contention for non-defending champions
                if (!$winnerStats->is_defending_champion) {
                    if ($winnerStats->conference_rank <= 4) {
                        $contentMiddles[] = "{$game->winner_team} strengthens their playoff position with a #{$winnerStats->conference_rank} conference rank.";
                    } elseif ($winnerStats->conference_rank <= 10) {
                        $contentMiddles[] = "{$game->winner_team} is battling for a play-in spot, currently sitting at #{$winnerStats->conference_rank} in the conference.";
                    } else {
                        $contentMiddles[] = "{$game->winner_team} continues fighting, but with a #{$winnerStats->conference_rank} rank, postseason hopes are slim.";
                    }
                }
            } else {
                // Early/mid-season news
                $contentMiddles[] = "{$game->winner_team} improved their standing with a Round {$game->round} victory, now holding #{$winnerStats->conference_rank} in the conference.";
            }
        }

        // Content enders
        // Base statements
        // Conditional enhancements
        $contentEnders = [];
        if ($winnerStats->is_defending_champion) {
            $contentEnders[] = "{$game->winner_team} proves they can defend their crown with another solid win.";
        }

        if ($loserStats->is_defending_champion) {
            $contentEnders[] = "{$game->winner_team} pulls off a statement win over the defending champion, shaking up the standings.";
        }

        if ($winnerStats->streak_status && preg_match('/W(\d+)/', $winnerStats->streak_status, $matches) && $matches[1] >= 3) {
            $streak = $matches[1];
            $contentEnders[] = "{$game->winner_team} extends their {$streak}-game winning streak, gaining momentum for the postseason.";
        }

        if ($isLast3Rounds) {
            // Winner is a top contender in the conference
            if ($winnerStats->conference_rank <= 6) {
                $contentEnders[] = "{$game->winner_team} keeps their playoff hopes alive, defeating {$loser} as the regular season nears its end.";
            }
            // Winner is a low-ranked team
            else {
                $contentEnders[] = "Despite being lower in the conference standings, {$game->winner_team} earns a crucial win against {$loser}, keeping their faint playoff hopes alive.";
            }

            // Loser is a top contender — now in danger
            if ($loserStats->conference_rank <= 6 && $loserStats->conference_rank > $winnerStats->conference_rank) {
                $contentEnders[] = "The loss puts {$loser} in danger of dropping out of playoff contention, while {$game->winner_team} climbs in the conference race.";
            }

            // Upset vs defending champion
            if ($loserStats->is_defending_champion) {
                $contentEnders[] = "{$game->winner_team} shocks the defending champion {$loser}, potentially altering the playoff picture in their conference!";
            }
        } else {
            // Case 2: Not last 3 rounds — regular season news
            // Winner is defending champion
            if ($winnerStats->is_defending_champion) {
                $contentEnders[] = "{$game->winner_team}, the defending champion, continues to assert their dominance in the conference.";
            }
            // Winner is top-ranked
            elseif ($winnerStats->conference_rank == 1) {
                $contentEnders[] = "{$game->winner_team} remains atop their conference, next facing {$winnerStats->next_opponent_name}.";
            }
            // Winner is mid-ranked (playoff contender)
            elseif ($winnerStats->conference_rank >= 2 && $winnerStats->conference_rank <= 6) {
                $contentEnders[] = "The win keeps {$game->winner_team} in strong playoff contention, aiming for a higher seed.";
            }
            // Winner is low-ranked (struggling)
            else {
                $contentEnders[] = "Despite their lower ranking, {$game->winner_team} secures a vital victory to stay competitive in the conference.";
            }

            // Upset scenarios
            if ($loserStats->is_defending_champion && $winnerStats->conference_rank > $loserStats->conference_rank) {
                $contentEnders[] = "{$game->winner_team} pulls off an upset over defending champion {$loser}, shaking up the playoff picture.";
            }
            // Non-champion upset: low-rank team beats higher-rank team
            elseif ($winnerStats->conference_rank > $loserStats->conference_rank) {
                $contentEnders[] = "In a surprising result, lower-ranked {$game->winner_team} defeats higher-ranked {$loser}, making waves in the conference standings.";
            }
        }
        // Highlight experienced teams
        if ($winnerStats->playoff_appearances > 5) {
            $contentEnders[] = "Veteran experience shines as {$game->winner_team} adds another regular season win to their impressive track record.";
        }

        if ($winnerStats->championships > 0) {
            $contentEnders[] = "With past championships fueling their confidence, {$game->winner_team} remains a team to watch this season.";
        }

        // Select random phrases
        $title = str_replace(
            ['{winner}', '{loser}', '{home_score}', '{away_score}', '{round}', '{player}'],
            [$game->winner_team, $loser, $game->home_score, $game->away_score, $game->round, $draftPick ? $draftPick->player_name : ''],
            $headlineTemplates[array_rand($headlineTemplates)]
        );

        // Build content with random segments
        $content = str_replace(
            ['{round}', '{winner}', '{loser}', '{home_score}', '{away_score}', '{player}', '{points}', '{rebounds}', '{assists}'],
            [
                $game->round,
                $game->winner_team,
                $loser,
                $game->home_score,
                $game->away_score,
                $topPerformer ? $topPerformer->player_name : ($draftPick ? $draftPick->player_name : ''),
                $topPerformer ? $topPerformer->points : 'N/A',
                $topPerformer ? $topPerformer->rebounds : 'N/A',
                $topPerformer ? $topPerformer->assists : 'N/A',
            ],
            $contentStarters[array_rand($contentStarters)]
        ) . ' ' . str_replace(
            ['{winner}', '{loser}', '{ppg}', '{win_percentage}', '{chemistry}', '{score_difference}', '{wins}', '{losses}', '{conference_rank}'],
            [
                $game->winner_team,
                $loser,
                $winnerStats ? number_format($winnerStats->home_ppg, 1) : 'N/A',
                $headToHead ? number_format($headToHead->win_percentage * 100, 1) : 'N/A',
                $teamInfo ? $teamInfo->chemistry : 'N/A',
                $winnerStats ? $winnerStats->score_difference : 'N/A',
                $winnerStats ? $winnerStats->wins : 'N/A',
                $winnerStats ? $winnerStats->losses : 'N/A',
                $winnerStats ? $winnerStats->conference_rank : 'N/A',
            ],
            $contentMiddles[array_rand($contentMiddles)]
        ) . ' ' . str_replace(
            ['{winner}', '{loser}', '{player}'],
            [$game->winner_team, $loser, $draftPick ? $draftPick->player_name : ''],
            $contentEnders[array_rand($contentEnders)]
        );

        // Build injury content
        $injuryContent = '';
        $winnerInjured = $injuredPlayers->where('team_id', $game->winner_id);
        $loserInjured = $injuredPlayers->where('team_id', $loserId);

        if ($winnerInjured->count() > 0) {
            $winnerInjuryList = $winnerInjured->map(function ($player) {
                $injuryType = $player->injury_type
                    ? ucwords(str_replace('_', ' ', $player->injury_type))
                    : 'Unknown Injury';
                $recoveryStatus = ($player->injury_recovery_games == 0 || is_null($player->injury_type))
                    ? 'expected to return next game'
                    : 'out for ' . $player->injury_recovery_games . ' games';
                return $player->player_name . ' (' . $injuryType . ', ' . $recoveryStatus . ')';
            })->implode(', ');
            $injuryContent .= " Despite injuries to $winnerInjuryList, {$game->winner_team} prevailed.";
        }

        if ($loserInjured->count() > 0) {
            $loserInjuryList = $loserInjured->map(function ($player) {
                $injuryType = $player->injury_type
                    ? ucwords(str_replace('_', ' ', $player->injury_type))
                    : 'Unknown Injury';
                $recoveryStatus = ($player->injury_recovery_games == 0 || is_null($player->injury_type))
                    ? 'expected to return next game'
                    : 'out for ' . $player->injury_recovery_games . ' games';
                return $player->player_name . ' (' . $injuryType . ', ' . $recoveryStatus . ')';
            })->implode(', ');
            $injuryContent .= " {$loser} was hampered by injuries to $loserInjuryList.";
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