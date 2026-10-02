<?php

namespace App\Services\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Services\Stats\PlayerStatsService;

class MinuteDistributionService
{
    protected $playerStats;

    public function __construct()
    {
        $this->playerStats = new PlayerStatsService();
    }

    /**
     * Distribute player minutes for ONE simulation segment.
     *
     * Quarter:
     * 5 players x 12 minutes = 60 team minutes
     *
     * Full game:
     * 5 players x 48 minutes = 240 team minutes
     *
     * The caller should pass 60 for a regulation quarter.
     *
     * Returns:
     *
     * [
     *     player_id => minutes
     * ]
     */
    public function distributeMinutes(
        $playersArray,
        int $totalMinutes,
        $gameId,
        string $coachingStyle = 'balanced',
        string $preferredTeamComposition = 'any'
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Validate Input
        |--------------------------------------------------------------------------
        */

        if ($totalMinutes <= 0) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Players
        |--------------------------------------------------------------------------
        |
        | getActivePlayersSorted() returns Collection<Player>.
        |
        */

        if ($playersArray instanceof Collection) {
            $players = $playersArray->values();
        } elseif (is_array($playersArray)) {
            $players = collect($playersArray)->values();
        } else {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Keep Eloquent Player Models
        |--------------------------------------------------------------------------
        */

        $players = $players
            ->filter(function ($player) {
                return $player instanceof Model
                    && !empty($player->id);
            })
            ->values();

        if ($players->isEmpty()) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Coaching Style
        |--------------------------------------------------------------------------
        */

        $coachingStyle = strtolower(
            trim((string) $coachingStyle)
        );

        $validStyles = [
            'balanced',
            'offensive',
            'defensive',
            'fast-paced',
            'slow-tempo',
        ];

        if (!in_array($coachingStyle, $validStyles, true)) {
            $coachingStyle = 'balanced';
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Team Composition
        |--------------------------------------------------------------------------
        */

        $preferredTeamComposition = strtolower(
            trim((string) $preferredTeamComposition)
        );

        $validCompositions = [
            'balanced',
            'offensive',
            'defensive',
            'any',
            'contenders',
            'rebuilding',
        ];

        if (!in_array(
            $preferredTeamComposition,
            $validCompositions,
            true
        )) {
            $preferredTeamComposition = 'any';
        }

        /*
        |--------------------------------------------------------------------------
        | Initialize Minutes
        |--------------------------------------------------------------------------
        */

        $minutes = [];

        foreach ($players as $player) {
            $minutes[$player->id] = 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Available Players
        |--------------------------------------------------------------------------
        |
        | Injured and fouled-out players receive zero minutes.
        |
        */

        $availablePlayers = $players
            ->filter(function ($player) {

                $isInjured = (bool) (
                    $player->is_injured ?? false
                );

                $isFouledOut = (bool) (
                    $player->is_fouled_out ?? false
                );

                return !$isInjured && !$isFouledOut;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | No Available Players
        |--------------------------------------------------------------------------
        */

        if ($availablePlayers->isEmpty()) {
            return $minutes;
        }

        /*
        |--------------------------------------------------------------------------
        | Role Priority
        |--------------------------------------------------------------------------
        */

        $rolePriority = [
            'star player' => 1,
            'all star'    => 2,
            'starter'     => 3,
            'role player' => 4,
            'bench'       => 5,
        ];

        /*
        |--------------------------------------------------------------------------
        | Role Base Scores
        |--------------------------------------------------------------------------
        */

        $roleBaseScores = [
            'star player' => 100,
            'all star'    => 88,
            'starter'     => 76,
            'role player' => 62,
            'bench'       => 48,
        ];

        /*
        |--------------------------------------------------------------------------
        | Minimum Minutes Per Quarter
        |--------------------------------------------------------------------------
        */

        $roleMinimumMinutes = [
            'star player' => 1,
            'all star'    => 1,
            'starter'     => 1,
            'role player' => 0,
            'bench'       => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Maximum Minutes Per Quarter
        |--------------------------------------------------------------------------
        |
        | These are based around a 12-minute quarter.
        |
        */

        $roleMaximumMinutes = [
            'star player' => 12,
            'all star'    => 11,
            'starter'     => 12,
            'role player' => 12,
            'bench'       => 12,
        ];

        /*
        |--------------------------------------------------------------------------
        | Coaching Style Modifiers
        |--------------------------------------------------------------------------
        */

        $styleModifiers = [

            'balanced' => [
                'top'   => 1.00,
                'depth' => 1.00,
            ],

            'offensive' => [
                'top'   => 1.20,
                'depth' => 0.90,
            ],

            'defensive' => [
                'top'   => 1.05,
                'depth' => 1.10,
            ],

            'fast-paced' => [
                'top'   => 0.95,
                'depth' => 1.20,
            ],

            'slow-tempo' => [
                'top'   => 1.20,
                'depth' => 0.80,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Team Composition Modifiers
        |--------------------------------------------------------------------------
        */

        $compositionModifiers = [

            'any' => [
                'top'   => 1.00,
                'depth' => 1.00,
            ],

            'balanced' => [
                'top'   => 1.00,
                'depth' => 1.00,
            ],

            'offensive' => [
                'top'   => 1.10,
                'depth' => 1.00,
            ],

            'defensive' => [
                'top'   => 1.00,
                'depth' => 1.10,
            ],

            'contenders' => [
                'top'   => 1.20,
                'depth' => 0.80,
            ],

            'rebuilding' => [
                'top'   => 0.80,
                'depth' => 1.25,
            ],
        ];

        $styleModifier =
            $styleModifiers[$coachingStyle];

        $compositionModifier =
            $compositionModifiers[
                $preferredTeamComposition
            ];

        /*
        |--------------------------------------------------------------------------
        | Player Priority Score
        |--------------------------------------------------------------------------
        */

        $getPriorityScore = function ($player) use (
            $rolePriority,
            $roleBaseScores,
            $styleModifier,
            $compositionModifier,
            $coachingStyle,
            $preferredTeamComposition
        ) {
            $role = strtolower(
                trim((string) (
                    $player->role ?? 'bench'
                ))
            );

            /*
            |--------------------------------------------------------------------------
            | Base Rating
            |--------------------------------------------------------------------------
            */

            $overall = (float) (
                $player->overall_rating
                ?? $player->overall
                ?? 50
            );

            $eff = (float) (
                $player->eff ?? 0
            );

            $per = (float) (
                $player->per ?? 0
            );

            /*
            |--------------------------------------------------------------------------
            | Performance
            |--------------------------------------------------------------------------
            */

            $points = (float) (
                $player->points ?? 0
            );

            $assists = (float) (
                $player->assists ?? 0
            );

            $steals = (float) (
                $player->steals ?? 0
            );

            $blocks = (float) (
                $player->blocks ?? 0
            );

            $rebounds = (float) (
                $player->rebounds ?? 0
            );

            /*
            |--------------------------------------------------------------------------
            | Base Quality
            |--------------------------------------------------------------------------
            */

            $ratingScore =
                ($overall * 0.45)
                + ($eff * 0.30)
                + ($per * 0.25);

            /*
            |--------------------------------------------------------------------------
            | Role
            |--------------------------------------------------------------------------
            */

            $roleBase =
                $roleBaseScores[$role] ?? 40;

            $roleRank =
                $rolePriority[$role] ?? 5;

            /*
            |--------------------------------------------------------------------------
            | Coaching + Composition
            |--------------------------------------------------------------------------
            */

            if ($roleRank <= 3) {

                $roleMultiplier =
                    $styleModifier['top']
                    * $compositionModifier['top'];

            } else {

                $roleMultiplier =
                    $styleModifier['depth']
                    * $compositionModifier['depth'];
            }

            $roleScore =
                $roleBase * $roleMultiplier;

            /*
            |--------------------------------------------------------------------------
            | Offensive Coach
            |--------------------------------------------------------------------------
            */

            if ($coachingStyle === 'offensive') {

                $ratingScore +=
                    ($points * 0.30)
                    + ($assists * 0.15);
            }

            /*
            |--------------------------------------------------------------------------
            | Defensive Coach
            |--------------------------------------------------------------------------
            */

            elseif ($coachingStyle === 'defensive') {

                $ratingScore +=
                    ($steals * 0.30)
                    + ($blocks * 0.30)
                    + ($rebounds * 0.10);
            }

            /*
            |--------------------------------------------------------------------------
            | Fast Pace
            |--------------------------------------------------------------------------
            */

            elseif ($coachingStyle === 'fast-paced') {

                $ratingScore +=
                    ($assists * 0.10)
                    + ($steals * 0.10)
                    + ($rebounds * 0.05);
            }

            /*
            |--------------------------------------------------------------------------
            | Slow Tempo
            |--------------------------------------------------------------------------
            */

            elseif ($coachingStyle === 'slow-tempo') {

                $ratingScore +=
                    ($points * 0.20)
                    + ($rebounds * 0.15);
            }

            /*
            |--------------------------------------------------------------------------
            | Rebuilding Preference
            |--------------------------------------------------------------------------
            */

            if (
                $preferredTeamComposition === 'rebuilding'
                && in_array(
                    $role,
                    [
                        'role player',
                        'bench',
                    ],
                    true
                )
            ) {
                $roleScore += 8;
            }

            /*
            |--------------------------------------------------------------------------
            | Contender Preference
            |--------------------------------------------------------------------------
            */

            if (
                $preferredTeamComposition === 'contenders'
                && in_array(
                    $role,
                    [
                        'star player',
                        'all star',
                        'starter',
                    ],
                    true
                )
            ) {
                $roleScore += 8;
            }

            /*
            |--------------------------------------------------------------------------
            | Fatigue
            |--------------------------------------------------------------------------
            */

            $fatigue = (float) (
                $player->fatigue ?? 0
            );

            $fatiguePenalty =
                min(
                    20,
                    max(0, $fatigue)
                ) * 0.25;

            /*
            |--------------------------------------------------------------------------
            | Return Score
            |--------------------------------------------------------------------------
            */

            return
                $roleScore
                + $ratingScore
                - $fatiguePenalty;
        };

        /*
        |--------------------------------------------------------------------------
        | Rank Players
        |--------------------------------------------------------------------------
        */

        $rankedPlayers = $availablePlayers
            ->sortByDesc(function ($player) use (
                $getPriorityScore
            ) {
                return $getPriorityScore($player);
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Rotation Size
        |--------------------------------------------------------------------------
        */

        $rotationSize = 8;

        if ($coachingStyle === 'fast-paced') {
            $rotationSize = 9;
        }

        if ($coachingStyle === 'slow-tempo') {
            $rotationSize = 7;
        }

        if (
            $preferredTeamComposition === 'rebuilding'
        ) {
            $rotationSize = max(
                9,
                $rotationSize
            );
        }

        $rotationSize = min(
            $rotationSize,
            $rankedPlayers->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Rotation
        |--------------------------------------------------------------------------
        */

        $rotation = $rankedPlayers
            ->take($rotationSize)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Adjust Maximums
        |--------------------------------------------------------------------------
        */

        $maximumMinutes =
            $roleMaximumMinutes;

        /*
        |--------------------------------------------------------------------------
        | Offensive / Slow Tempo
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $coachingStyle,
                [
                    'offensive',
                    'slow-tempo',
                ],
                true
            )
        ) {

            $maximumMinutes['star player']++;
            $maximumMinutes['all star']++;
        }

        /*
        |--------------------------------------------------------------------------
        | Fast Pace
        |--------------------------------------------------------------------------
        */

        if ($coachingStyle === 'fast-paced') {

            $maximumMinutes['role player']++;
            $maximumMinutes['bench']++;
        }

        /*
        |--------------------------------------------------------------------------
        | Position Capacity
        |--------------------------------------------------------------------------
        |
        | A 60-minute quarter has:
        |
        | PG = 12
        | SG = 12
        | SF = 12
        | PF = 12
        | C  = 12
        |
        */

        $positionCapacity =
            $totalMinutes / 5;

        $positionUsed = [
            'PG' => 0,
            'SG' => 0,
            'SF' => 0,
            'PF' => 0,
            'C'  => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Initial Random Allocation
        |--------------------------------------------------------------------------
        */

        foreach ($rotation as $player) {

            $id = $player->id;

            $role = strtolower(
                trim((string) (
                    $player->role ?? 'bench'
                ))
            );

            $position = strtoupper(
                trim((string) (
                    $player->position ?? 'SG'
                ))
            );

            $minimum =
                $roleMinimumMinutes[$role]
                ?? 0;

            $maximum =
                $maximumMinutes[$role]
                ?? 6;

            /*
            |--------------------------------------------------------------------------
            | Priority
            |--------------------------------------------------------------------------
            */

            $score =
                $getPriorityScore($player);

            /*
            |--------------------------------------------------------------------------
            | Convert Score To Quality Factor
            |--------------------------------------------------------------------------
            */

            $qualityFactor = min(
                1,
                max(
                    0,
                    ($score - 40) / 120
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Randomness
            |--------------------------------------------------------------------------
            */

            $randomFactor =
                random_int(0, 100) / 100;

            /*
            |--------------------------------------------------------------------------
            | Combined Factor
            |--------------------------------------------------------------------------
            */

            $factor =
                ($qualityFactor * 0.70)
                + ($randomFactor * 0.30);

            /*
            |--------------------------------------------------------------------------
            | Assign Minutes
            |--------------------------------------------------------------------------
            */

            $assigned =
                $minimum
                + (int) round(
                    ($maximum - $minimum)
                    * $factor
                );

            /*
            |--------------------------------------------------------------------------
            | Position Capacity
            |--------------------------------------------------------------------------
            */

            if (isset($positionUsed[$position])) {

                $remaining =
                    $positionCapacity
                    - $positionUsed[$position];

                $assigned = min(
                    $assigned,
                    max(
                        0,
                        (int) floor($remaining)
                    )
                );

                $positionUsed[$position] +=
                    $assigned;
            }

            $minutes[$id] = $assigned;
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure Five Players Have Minutes
        |--------------------------------------------------------------------------
        */

        $playersWithMinutes =
            collect($minutes)
                ->filter(function ($value) {
                    return $value > 0;
                })
                ->count();

        if ($playersWithMinutes < 5) {

            foreach ($rankedPlayers as $player) {

                if ($playersWithMinutes >= 5) {
                    break;
                }

                $id = $player->id;

                if (($minutes[$id] ?? 0) <= 0) {

                    $minutes[$id] = 1;

                    $playersWithMinutes++;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Current Total
        |--------------------------------------------------------------------------
        */

        $currentTotal =
            array_sum($minutes);

        /*
        |--------------------------------------------------------------------------
        | Add Minutes Until Budget Is Reached
        |--------------------------------------------------------------------------
        */

        $safety = 0;

        while (
            $currentTotal < $totalMinutes
            && $safety < 1000
        ) {

            $safety++;

            $added = false;

            /*
            |--------------------------------------------------------------------------
            | Players With Highest Priority + Need
            |--------------------------------------------------------------------------
            */

            $candidates = $rankedPlayers
                ->sortByDesc(function ($player) use (
                    $getPriorityScore,
                    $minutes
                ) {

                    $id = $player->id;

                    $current =
                        $minutes[$id] ?? 0;

                    $needBonus =
                        max(
                            0,
                            12 - $current
                        ) * 1.5;

                    return
                        $getPriorityScore($player)
                        + $needBonus;
                })
                ->values();

            foreach ($candidates as $player) {

                $id = $player->id;

                $role = strtolower(
                    trim((string) (
                        $player->role ?? 'bench'
                    ))
                );

                $position = strtoupper(
                    trim((string) (
                        $player->position ?? 'SG'
                    ))
                );

                $maximum =
                    $maximumMinutes[$role]
                    ?? 6;

                $current =
                    $minutes[$id] ?? 0;

                /*
                |--------------------------------------------------------------------------
                | Role Limit
                |--------------------------------------------------------------------------
                */

                if ($current >= $maximum) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Position Limit
                |--------------------------------------------------------------------------
                */

                if (
                    isset($positionUsed[$position])
                    && $positionUsed[$position]
                    >= $positionCapacity
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Add One Minute
                |--------------------------------------------------------------------------
                */

                $minutes[$id] =
                    $current + 1;

                if (
                    isset($positionUsed[$position])
                ) {
                    $positionUsed[$position]++;
                }

                $currentTotal++;

                $added = true;

                break;
            }

            if (!$added) {
                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Fill
        |--------------------------------------------------------------------------
        |
        | If position limits prevent reaching the budget,
        | fill the remaining minutes from the rotation.
        |
        */

        $currentTotal =
            array_sum($minutes);

        if ($currentTotal < $totalMinutes) {

            $difference =
                $totalMinutes - $currentTotal;

            $rotationCount =
                $rotation->count();

            if ($rotationCount > 0) {

                $index = 0;

                while ($difference > 0) {

                    $player =
                        $rotation[
                            $index % $rotationCount
                        ];

                    $id = $player->id;

                    $minutes[$id] =
                        ($minutes[$id] ?? 0) + 1;

                    $difference--;

                    $index++;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Excess Minutes
        |--------------------------------------------------------------------------
        */

        $currentTotal =
            array_sum($minutes);

        if ($currentTotal > $totalMinutes) {

            $difference =
                $currentTotal - $totalMinutes;

            $candidates = $rotation
                ->sortBy(function ($player) use (
                    $getPriorityScore
                ) {
                    return $getPriorityScore($player);
                })
                ->values();

            $index = 0;
            $safety = 0;

            while (
                $difference > 0
                && $candidates->count() > 0
                && $safety < 1000
            ) {

                $safety++;

                $player =
                    $candidates[
                        $index % $candidates->count()
                    ];

                $id = $player->id;

                $role = strtolower(
                    trim((string) (
                        $player->role ?? 'bench'
                    ))
                );

                $minimum =
                    $roleMinimumMinutes[$role]
                    ?? 0;

                if (
                    ($minutes[$id] ?? 0)
                    > $minimum
                ) {

                    $minutes[$id]--;

                    $difference--;
                }

                $index++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Normalization
        |--------------------------------------------------------------------------
        */

        foreach ($minutes as $id => $value) {

            $minutes[$id] =
                max(
                    0,
                    (int) $value
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Apply Fatigue
        |--------------------------------------------------------------------------
        */

        foreach ($players as $player) {

            $id = $player->id;

            $playerMinutes =
                $minutes[$id] ?? 0;

            if ($playerMinutes <= 0) {
                continue;
            }

            $this->playerStats->applyFatigue(
                $player,
                $playerMinutes,
                $gameId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Return Minutes
        |--------------------------------------------------------------------------
        */

        return $minutes;
    }
}