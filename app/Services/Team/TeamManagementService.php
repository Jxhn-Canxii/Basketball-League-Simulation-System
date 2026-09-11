<?php

namespace App\Services\Team;

use App\Models\Player;
use Illuminate\Support\Facades\DB;
use App\Services\Stats\PlayerSeasonStatsService;
use App\Services\Helper\HelperService;
use App\Services\Player\FreeAgencyService;


class TeamManagementService
{
    protected $teamWaiving;

    protected $helper;
    protected $freeAgencyService;
    protected $teamInjury;

    public function __construct()
    {
        $this->helper = new HelperService();
        $this->freeAgencyService = new FreeAgencyService();
        $this->teamInjury = new TeamInjuryService();
        $this->teamWaiving = new TeamWaivingService();
        
    }

    public function getActivePlayersSorted($teamId, $gameId, $rolePriority, $round)
    {
        $seasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id(); // You must implement this

        $players = Player::where('team_id', $teamId)
            ->where('is_active', 1)
            ->get();

        $playerEfficiencies = [];

        foreach ($players as $player) {
            $playerId = $player->id;
            $role = $player->role;

            // Years pro = distinct seasons
            $yearsPro = DB::table('player_season_stats_archives')
                ->where('player_id', $playerId)
                ->distinct('season_id')
                ->count('season_id') + 1;

            // Current season efficiency sum
            $currentEff = DB::table('player_season_stats')
                ->where('season_id', $seasonId)
                ->where('player_id', $playerId)
                ->sum('eff') ?? 0;

            // Last 5 games from previous season (only if early season)
            $lastFiveGamesEff = DB::table('player_game_stats')
                ->where('season_id', $previousSeasonId)
                ->where('player_id', $playerId)
                ->orderByDesc('id')
                ->limit(5)
                ->sum('eff') ?? 0;

            $totalEff = $currentEff + $lastFiveGamesEff;

            // Draft info
            $draft = DB::table('drafts')
                ->where('player_id', $playerId)
                ->where('season_id', $seasonId)
                ->first();

            $playerFouls = DB::table('player_per_quarter_stats')
                ->where('player_id', $playerId)
                ->where('game_id', $gameId)
                ->sum('fouls');

            $isFouledOut = ($playerFouls >= 5) ? 1 : 0;

            $player->is_fouled_out = $isFouledOut;
            $player->last_quarter_fouls = $playerFouls;

            $playerEfficiencies[] = [
                'player' => $player,
                'role' => $player->role,
                'total_eff' => $totalEff,
                'years_pro' => $yearsPro,
                'is_rookie' => $draft ? true : false,
                'draft_round' => $draft->round ?? null,
                'draft_pick' => $draft->pick_number ?? null,
                'is_fouled_out' =>  $isFouledOut,
                'fatigue' =>  $player->fatigue,
                'role_rank' => array_search($player->role, $rolePriority) !== false
                    ? array_search($player->role, $rolePriority)
                    : PHP_INT_MAX,
            ];
        }

        // Sort by: total_eff DESC, role_priority ASC,fatigue ASC,years_pro DESC,
        $sortedPlayers = collect($playerEfficiencies)->sort(function ($a, $b) {
            return $b['total_eff'] <=> $a['total_eff']
                ?: $a['role_rank'] <=> $b['role_rank']
                ?: $a['fatigue'] <=> $b['fatigue']
                ?: $b['years_pro'] <=> $a['years_pro'];
        })->pluck('player')->values();

        return $sortedPlayers;
    }

    public function prepareFinalRoster($teamId, $round = null)
    {
        $seasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id();


        $coach = DB::table('coaches')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Get Team Players
        |--------------------------------------------------------------------------
        */

        $players = DB::table('players as players')
            ->select(
                'players.*',
                'drafts.round as draft_round',
                'drafts.pick_number as draft_pick'
            )
            ->leftJoin('drafts', function ($join) use ($seasonId) {
                $join->on('drafts.player_id', '=', 'players.id')
                    ->where('drafts.season_id', '=', $seasonId);
            })
            ->where('players.team_id', $teamId)
            ->where('players.is_active', 1)
            ->get();

        if ($players->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active players found.',
            ];
        }

            /*
        |--------------------------------------------------------------------------
        | Coach Preferences
        |--------------------------------------------------------------------------
        |
        | These should match your coach table.
        |
        */

        $coachingStyle = strtolower($coach->coaching_style ?? 'balanced');

        $coachOffense = (int) ($coach->offensive_rating ?? 50);
        $coachDefense = (int) ($coach->defensive_rating ?? 50);
        $coachDevelopment = (int) ($coach->development_rating ?? 50);

            /*
        |--------------------------------------------------------------------------
        | Position Needs
        |--------------------------------------------------------------------------
        |
        | We don't want the coach to simply take the 12 highest-efficiency
        | players if that creates an unbalanced roster.
        |
        */

        $positionNeeds = $this->getTeamPositionNeeds($teamId);

            /*
        |--------------------------------------------------------------------------
        | Evaluate Every Player
        |--------------------------------------------------------------------------
        */

        $evaluatedPlayers = [];

        foreach ($players as $player) {

            $playerId = $player->id;

            /*
            |--------------------------------------------------------------------------
            | Current Season Stats
            |--------------------------------------------------------------------------
            */

            $stats = DB::table('player_season_stats')
                ->where('player_id', $playerId)
                ->where('season_id', $seasonId)
                ->first();

            $efficiency = (float) ($stats->eff ?? 0);

            $minutes = (float) ($stats->avg_minutes_per_game ?? 0);

            $points = (float) ($stats->avg_points_per_game ?? 0);

            $rebounds = (float) ($stats->avg_rebounds_per_game ?? 0);

            $assists = (float) ($stats->avg_assists_per_game ?? 0);

            /*
            |--------------------------------------------------------------------------
            | Years Pro
            |--------------------------------------------------------------------------
            */

            $yearsPro = DB::table('player_season_stats_archives')
                ->where('player_id', $playerId)
                ->distinct()
                ->count('season_id');

            $yearsPro++;

            /*
            |--------------------------------------------------------------------------
            | Rookie Detection
            |--------------------------------------------------------------------------
            */

            $isRookie = (int) ($player->is_rookie ?? 0) === 1;

            /*
            |--------------------------------------------------------------------------
            | Draft Importance
            |--------------------------------------------------------------------------
            */

            $draftBonus = 0;

            if (!empty($player->draft_round)) {

                if ((int) $player->draft_round === 1) {

                    $draftBonus += 8;

                    if (!empty($player->draft_pick)) {

                        if ($player->draft_pick <= 10) {
                            $draftBonus += 8;
                        } elseif ($player->draft_pick <= 20) {
                            $draftBonus += 4;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Player Overall
            |--------------------------------------------------------------------------
            */

            $overall = (float) ($player->overall_rating ?? 50);

            /*
            |--------------------------------------------------------------------------
            | Coach Role Fit
            |--------------------------------------------------------------------------
            */

            $roleFit = $this->getCoachRoleFit(
                $player,
                $coach
            );

            /*
            |--------------------------------------------------------------------------
            | Position Fit
            |--------------------------------------------------------------------------
            */
            $positionFit = $this->getPositionNeedScore(
                $player->position ?? null,
                $positionNeeds
            );

            /*
            |--------------------------------------------------------------------------
            | Development Bonus
            |--------------------------------------------------------------------------
            */

            $developmentBonus = 0;

            if ($isRookie) {

                /*
             * A development-oriented coach values rookies more.
             */

                $developmentBonus =
                    (($coachDevelopment - 50) / 10);
            } else {

                /*
             * Veteran players receive a small bonus when
             * the coach is less development focused.
             */

                if ($yearsPro >= 5) {
                    $developmentBonus =
                        ((50 - $coachDevelopment) / 20);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Efficiency Score
            |--------------------------------------------------------------------------
            */

            $efficiencyScore = min(
                25,
                max(0, $efficiency * 1.5)
            );

            /*
            |--------------------------------------------------------------------------
            | Overall Score
            |--------------------------------------------------------------------------
            */

            $overallScore = $overall * 0.30;

            /*
            |--------------------------------------------------------------------------
            | Coach System Score
            |--------------------------------------------------------------------------
            */

            $coachScore = $roleFit * 0.15;

            /*
            |--------------------------------------------------------------------------
            | Position Score
            |--------------------------------------------------------------------------
            */

            $positionScore = $positionFit * 0.10;

            /*
            |--------------------------------------------------------------------------
            | Performance Score
            |--------------------------------------------------------------------------
            */

            $performanceScore = $efficiencyScore * 0.20;

            /*
            |--------------------------------------------------------------------------
            | Experience
            |--------------------------------------------------------------------------
            */

            $experienceScore = min(
                10,
                $yearsPro * 1.5
            );

            $experienceScore *= 0.05;

            /*
            |--------------------------------------------------------------------------
            | Morale
            |--------------------------------------------------------------------------
            */

            $morale = (float) ($player->morale ?? 50);

            $moraleScore = max(
                -5,
                min(5, ($morale - 50) / 10)
            );

            /*
            |--------------------------------------------------------------------------
            | Injury Penalty
            |--------------------------------------------------------------------------
            */

            $injuryPenalty = 0;

            if ((int) ($player->is_injured ?? 0) === 1) {
                $injuryPenalty = 20;
            }

            /*
            |--------------------------------------------------------------------------
            | Fatigue Penalty
            |--------------------------------------------------------------------------
            */

            $fatigue = (float) ($player->fatigue ?? 0);

            $fatiguePenalty = min(
                10,
                $fatigue / 10
            );

            /*
            |--------------------------------------------------------------------------
            | Final Coach Evaluation
            |--------------------------------------------------------------------------
            */

            $coachEvaluation =
                $overallScore
                + $coachScore
                + $positionScore
                + $performanceScore
                + $experienceScore
                + $developmentBonus
                + $draftBonus
                + $moraleScore
                - $injuryPenalty
                - $fatiguePenalty;

            $evaluatedPlayers[] = [
                'player' => $player,
                'player_id' => $playerId,
                'score' => round($coachEvaluation, 3),
                'efficiency' => $efficiency,
                'overall' => $overall,
                'years_pro' => $yearsPro,
                'role_fit' => $roleFit,
                'position_fit' => $positionFit,
                'draft_bonus' => $draftBonus,
                'is_rookie' => $isRookie,
                'is_injured' => (int) ($player->is_injured ?? 0),
                'fatigue' => $fatigue,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Sort By Coach Evaluation
        |--------------------------------------------------------------------------
        */

        $evaluatedPlayers = collect($evaluatedPlayers)
            ->sort(function ($a, $b) {

                return $b['score'] <=> $a['score']
                    ?: $b['overall'] <=> $a['overall']
                    ?: $b['efficiency'] <=> $a['efficiency']
                    ?: $a['fatigue'] <=> $b['fatigue'];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Select Final 12
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We use slice(0, 12), NOT slice(1, 12).
        |
        */

        $final12 = $evaluatedPlayers
            ->take(12)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Reserved 3
        |--------------------------------------------------------------------------
        */

        $reserved3 = $evaluatedPlayers
            ->slice(12, 3)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Apply Final 12
        |--------------------------------------------------------------------------
        */

        foreach ($final12 as $evaluation) {

            $player = $evaluation['player'];

            $newFatigue = $this->teamInjury->fatigueAdjustment(
                $player->morale ?? 50,
                $player->fatigue ?? 0
            );

            DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'is_reserved' => 0,
                    'fatigue' => $newFatigue,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Update Season Stats Role
            |--------------------------------------------------------------------------
            */

            DB::table('player_season_stats')
                ->where('player_id', $player->id)
                ->where('team_id', $teamId)
                ->where('season_id', $seasonId)
                ->update([
                    'role' => $player->role,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Apply Reserved 3
        |--------------------------------------------------------------------------
        */

        foreach ($reserved3 as $evaluation) {

            $player = $evaluation['player'];

            /*
            |--------------------------------------------------------------------------
            | Reserved players receive reduced fatigue.
            |--------------------------------------------------------------------------
            */

            if ((int) ($player->is_injured ?? 0) === 1) {

                $newFatigue = max(
                    0,
                    ($player->fatigue ?? 0) - 5
                );
            } else {

                $newFatigue = 0;
            }

            DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'is_reserved' => 1,
                    'fatigue' => $newFatigue,
                    'role' => 'bench',
                ]);

            /*
            |--------------------------------------------------------------------------
            | Correct player_season_stats row
            |--------------------------------------------------------------------------
            */

            DB::table('player_season_stats')
                ->where('player_id', $player->id)
                ->where('team_id', $teamId)
                ->where('season_id', $seasonId)
                ->update([
                    'role' => 'bench',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Return Decision Information
        |--------------------------------------------------------------------------
        */

        return [
            'success' => true,
            'team_id' => $teamId,
            'season_id' => $seasonId,
            'final_12' => $final12->map(function ($evaluation) {
                return [
                    'player_id' => $evaluation['player_id'],
                    'name' => $evaluation['player']->name,
                    'score' => $evaluation['score'],
                    'overall' => $evaluation['overall'],
                    'efficiency' => $evaluation['efficiency'],
                    'role_fit' => $evaluation['role_fit'],
                    'position_fit' => $evaluation['position_fit'],
                ];
            })->values(),

            'reserved_3' => $reserved3->map(function ($evaluation) {
                return [
                    'player_id' => $evaluation['player_id'],
                    'name' => $evaluation['player']->name,
                    'score' => $evaluation['score'],
                    'overall' => $evaluation['overall'],
                    'efficiency' => $evaluation['efficiency'],
                    'role_fit' => $evaluation['role_fit'],
                    'position_fit' => $evaluation['position_fit'],
                ];
            })->values(),
        ];
    }

    public function evaluatePlayerInjury($teamId)
    {
        $seasonId = get_current_season_id();

        $seasonStatus = $this->helper->seasonStatus($seasonId);

        if (!$teamId || !$seasonId || !$seasonStatus) {
            return; // Invalid parameters
        }

        $players = DB::table('players')->where('team_id', $teamId)->get();
        foreach ($players as $player) {
            $this->teamInjury->handleInjuredPlayer($player, $seasonId, $seasonStatus);
        }
    }


    private function getCoachRoleFit($player, $coach): float
    {
        $role = strtolower(trim($player->role ?? 'bench'));

        $style = strtolower(
            trim($coach->coaching_style ?? 'balanced')
        );

        /*
        |--------------------------------------------------------------------------
        | Base value by player role
        |--------------------------------------------------------------------------
        */

        $roleValues = [
            'star player' => 95,
            'all star'    => 90,
            'starter'     => 82,
            'role player' => 68,
            'bench'       => 50,
        ];

        $score = $roleValues[$role] ?? 50;

        /*
        |--------------------------------------------------------------------------
        | Player Ratings
        |--------------------------------------------------------------------------
        */

        $offense = (float) ($player->offensive_rating ?? 50);
        $defense = (float) ($player->defense_rating ?? 50);
        $overall = (float) ($player->overall_rating ?? 50);

        /*
        |--------------------------------------------------------------------------
        | Coach Ratings
        |--------------------------------------------------------------------------
        */

        $coachOffense = (float) (
            $coach->offensive_rating ?? 50
        );

        $coachDefense = (float) (
            $coach->defensive_rating ?? 50
        );

        $coachDevelopment = (float) (
            $coach->development_rating ?? 50
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize coach preference
        |--------------------------------------------------------------------------
        |
        | 50 = neutral
        | 100 = very strong preference
        |
        */

        $offensePreference =
            ($coachOffense - 50) / 50;

        $defensePreference =
            ($coachDefense - 50) / 50;

        $developmentPreference =
            ($coachDevelopment - 50) / 50;

        /*
        |--------------------------------------------------------------------------
        | How well player fits coach's offense
        |--------------------------------------------------------------------------
        */

        $offensiveFit =
            ($offense - 50) *
            $offensePreference *
            0.20;

        /*
        |--------------------------------------------------------------------------
        | How well player fits coach's defense
        |--------------------------------------------------------------------------
        */

        $defensiveFit =
            ($defense - 50) *
            $defensePreference *
            0.20;

        $score += $offensiveFit;
        $score += $defensiveFit;

        /*
        |--------------------------------------------------------------------------
        | Coaching Style
        |--------------------------------------------------------------------------
        */

        switch ($style) {

            case 'offensive':

                /*
                * Offensive coaches strongly prefer offensive players.
                */

                $score += ($offense - 50) * 0.25;

                break;

            case 'defensive':

                /*
                * Defensive coaches strongly prefer defensive players.
                */

                $score += ($defense - 50) * 0.25;

                break;

            case 'fast-paced':

                /*
                * Fast-paced teams need players capable of
                * sustaining a high level of play.
                */

                $score += ($overall - 50) * 0.10;
                $score += ($offense - 50) * 0.10;

                break;

            case 'slow-tempo':

                /*
                * Slow-tempo coaches prioritize efficient,
                * reliable players.
                */

                $score += ($offense - 50) * 0.15;
                $score += ($defense - 50) * 0.10;

                break;

            case 'balanced':
            default:

                /*
                * Balanced coach values both sides.
                */

                $balancedRating =
                    ($offense + $defense) / 2;

                $score +=
                    ($balancedRating - 50) * 0.20;

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Development Coach
        |--------------------------------------------------------------------------
        |
        | A development-oriented coach is more willing to keep
        | young/rookie players.
        */

        $age = (int) ($player->age ?? 25);

        $isYoung =
            $age <= 23;

        $isVeteran =
            $age >= 30;

        if ($isYoung) {

            $score +=
                $developmentPreference * 15;

        } elseif ($isVeteran) {

            /*
            * Development coaches slightly reduce preference
            * for older players.
            */

            $score -=
                $developmentPreference * 5;
        }

        /*
        |--------------------------------------------------------------------------
        | Role Compatibility
        |--------------------------------------------------------------------------
        |
        | Some coaching styles value certain roles differently.
        */

        if ($style === 'offensive') {

            if (in_array($role, [
                'star player',
                'all star',
                'starter',
            ])) {
                $score += 5;
            }

        } elseif ($style === 'defensive') {

            if (in_array($role, [
                'starter',
                'role player',
            ])) {
                $score += 5;
            }

        } elseif ($style === 'balanced') {

            if (in_array($role, [
                'starter',
                'role player',
            ])) {
                $score += 3;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Clamp
        |--------------------------------------------------------------------------
        */

        return round(
            max(0, min(100, $score)),
            2
        );
    }

    private function getPositionNeedScore(
    $position,
    array $positionNeeds
    ): float 
    {
        if (!$position) {
            return 40;
        }

        $positions = preg_split(
            '/[\/\-,]+/',
            strtoupper(trim($position))
        );

        $scores = [];

        foreach ($positions as $pos) {

            $pos = trim($pos);

            if (isset($positionNeeds[$pos])) {
                $scores[] = $positionNeeds[$pos]['need_score'];
            }
        }

        if (empty($scores)) {
            return 40;
        }

        return max($scores);
    }

    private function getTeamPositionNeeds(int $teamId): array
    {
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];

        /*
        |--------------------------------------------------------------------------
        | Target roster depth
        |--------------------------------------------------------------------------
        |
        | 15-man roster:
        |
        | PG 3
        | SG 3
        | SF 3
        | PF 3
        | C  3
        |
        */

        $ideal = [
            'PG' => 3,
            'SG' => 3,
            'SF' => 3,
            'PF' => 3,
            'C'  => 3,
        ];

        $players = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->get([
                'id',
                'position',
                'overall_rating',
                'offensive_rating',
                'defense_rating',
                'is_reserved',
                'is_injured',
                'role',
                'morale',
                'fatigue',
            ]);

        $positionData = [];

        foreach ($positions as $position) {

            $positionData[$position] = [
                'active_count' => 0,
                'reserved_count' => 0,
                'effective_depth' => 0,
                'quality_total' => 0,
                'quality_count' => 0,
                'average_quality' => 0,
                'best_quality' => 0,
                'injured_count' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Analyze players
        |--------------------------------------------------------------------------
        */

        foreach ($players as $player) {

            $playerPosition = strtoupper(
                trim($player->position ?? '')
            );

            if (!$playerPosition) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Support multi-position players
            |--------------------------------------------------------------------------
            |
            | PG/SG
            | SG-SF
            | SF/PF
            |
            */

            $playerPositions = preg_split(
                '/[\/\-,]+/',
                $playerPosition
            );

            $playerPositions = array_values(
                array_filter(
                    array_map('trim', $playerPositions)
                )
            );

            foreach ($playerPositions as $position) {

                if (!isset($positionData[$position])) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Active / Reserved Depth
                |--------------------------------------------------------------------------
                */

                if ((int) ($player->is_reserved ?? 0) === 1) {

                    $positionData[$position]['reserved_count']++;

                    /*
                    * Reserved player counts as half depth.
                    */

                    $positionData[$position]['effective_depth'] += 0.5;

                } else {

                    $positionData[$position]['active_count']++;

                    $positionData[$position]['effective_depth'] += 1.0;
                }

                /*
                |--------------------------------------------------------------------------
                | Player Quality
                |--------------------------------------------------------------------------
                |
                | Overall is the primary quality indicator.
                | Offensive + defensive ratings help the coach
                | evaluate the actual usefulness of the player.
                */

                $overall = (float) ($player->overall_rating ?? 50);

                $offense = (float) ($player->offensive_rating ?? 50);

                $defense = (float) ($player->defense_rating ?? 50);

                $quality = (
                    ($overall * 0.50) +
                    ($offense * 0.25) +
                    ($defense * 0.25)
                );

                /*
                |--------------------------------------------------------------------------
                | Injury penalty
                |--------------------------------------------------------------------------
                */

                if ((int) ($player->is_injured ?? 0) === 1) {
                    $quality *= 0.75;

                    $positionData[$position]['injured_count']++;
                }

                /*
                |--------------------------------------------------------------------------
                | Fatigue penalty
                |--------------------------------------------------------------------------
                */

                $fatigue = (float) ($player->fatigue ?? 0);

                if ($fatigue > 70) {

                    $quality *= 0.90;

                } elseif ($fatigue > 50) {

                    $quality *= 0.95;
                }

                /*
                |--------------------------------------------------------------------------
                | Store Quality
                |--------------------------------------------------------------------------
                */

                $positionData[$position]['quality_total'] += $quality;

                $positionData[$position]['quality_count']++;

                $positionData[$position]['best_quality'] = max(
                    $positionData[$position]['best_quality'],
                    $quality
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Positional Need
        |--------------------------------------------------------------------------
        */

        $needs = [];

        foreach ($positions as $position) {

            $data = $positionData[$position];

            $effectiveDepth = $data['effective_depth'];

            $qualityCount = $data['quality_count'];

            $averageQuality = $qualityCount > 0
                ? $data['quality_total'] / $qualityCount
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Depth Score
            |--------------------------------------------------------------------------
            |
            | 0 players = 100 need
            | 1 player  = 95
            | 2 players = 75
            | 3 players = 40
            | 4 players = 20
            | 5+        = 10
            |
            */

            if ($effectiveDepth <= 0) {

                $depthScore = 100;

            } elseif ($effectiveDepth < 1.5) {

                $depthScore = 95;

            } elseif ($effectiveDepth < 2.5) {

                $depthScore = 75;

            } elseif ($effectiveDepth < 3.5) {

                $depthScore = 40;

            } elseif ($effectiveDepth < 4.5) {

                $depthScore = 20;

            } else {

                $depthScore = 10;
            }

            /*
            |--------------------------------------------------------------------------
            | Quality Score
            |--------------------------------------------------------------------------
            |
            | If the position has players but they're weak,
            | the coach still considers the position a need.
            |
            */

            if ($averageQuality <= 0) {

                $qualityScore = 100;

            } elseif ($averageQuality < 50) {

                $qualityScore = 95;

            } elseif ($averageQuality < 60) {

                $qualityScore = 80;

            } elseif ($averageQuality < 70) {

                $qualityScore = 65;

            } elseif ($averageQuality < 80) {

                $qualityScore = 45;

            } elseif ($averageQuality < 90) {

                $qualityScore = 25;

            } else {

                $qualityScore = 10;
            }

            /*
            |--------------------------------------------------------------------------
            | Star Player Protection
            |--------------------------------------------------------------------------
            |
            | If there is at least one excellent player at the position,
            | reduce the positional need.
            |
            */

            $starProtection = 0;

            if ($data['best_quality'] >= 90) {

                $starProtection = 20;

            } elseif ($data['best_quality'] >= 85) {

                $starProtection = 10;
            }

            /*
            |--------------------------------------------------------------------------
            | Injury Need
            |--------------------------------------------------------------------------
            |
            | Injured players shouldn't count as reliable depth.
            |
            */

            $injuryPenalty = 0;

            if ($data['injured_count'] > 0) {

                $injuryPenalty = min(
                    20,
                    $data['injured_count'] * 7
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Final Need Score
            |--------------------------------------------------------------------------
            |
            | Depth = 45%
            | Quality = 45%
            | Injury = 10%
            |
            */

            $needScore =
                ($depthScore * 0.45) +
                ($qualityScore * 0.45) +
                ($injuryPenalty * 0.10);

            /*
            |--------------------------------------------------------------------------
            | Star player reduces need
            |--------------------------------------------------------------------------
            */

            $needScore -= $starProtection;

            $needScore = max(
                5,
                min(100, $needScore)
            );

            $needs[$position] = [
                'active_count' => $data['active_count'],
                'reserved_count' => $data['reserved_count'],
                'effective_depth' => round(
                    $effectiveDepth,
                    1
                ),

                'average_quality' => round(
                    $averageQuality,
                    1
                ),

                'best_quality' => round(
                    $data['best_quality'],
                    1
                ),

                'injured_count' => $data['injured_count'],

                'depth_score' => round(
                    $depthScore,
                    1
                ),

                'quality_score' => round(
                    $qualityScore,
                    1
                ),

                'need_score' => round(
                    $needScore,
                    1
                ),
            ];
        }

        return $needs;
    }

}
