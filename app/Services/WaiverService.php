<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AwardsController;

class WaiverService
{
    private const MIN_GAMES_BETWEEN_PICKUPS = 4;
    private const MIN_PER_GAP = 5.0;
    private const MIN_HEALTHY_PER_POSITION = 3;
    private const ONE_PICKUP_PER_SIM_ROUND = true;
    private const RANDOM_PASS_THRESHOLD = 0.70;
    private const STAR_PER_THRESHOLD = 18;

    private const W_PERFORMANCE      = 0.25;
    private const W_POSITION_NEED    = 0.25;
    private const W_ROLE_FIT         = 0.15;
    private const W_USAGE_RECENT     = 0.15;
    private const W_AGE_FIT          = 0.10;
    private const W_AWARD_BONUS      = 0.10;

    private const PRESTIGE_AWARDS = [
        'Best Overall Player',
        'Best Defensive Player',
        'Most Efficient Player',
        'Top Scorer',
        'Top Rebounder',
        'Top Playmaker',
    ];

    public function pickUpFreeAgentForTeam(int $teamId, int $roundId): void
    {
        try {
            $seasonId = $this->getCurrentSeasonId() ?? 1;
            $seasonPhase = DB::table('seasons')->where('id', $seasonId)->value('status');
            if ($seasonPhase > 2) return;

            if (self::ONE_PICKUP_PER_SIM_ROUND && Cache::get("waiver_done_T{$teamId}_R{$roundId}")) return;

            $lastPickup = DB::table('transactions')
                ->where('to_team_id', $teamId)
                ->where('status', 'signed')
                ->where('season_id', $seasonId)
                ->latest()->first();

            if ($lastPickup && $this->countTeamGamesSince($teamId, $lastPickup->created_at) < self::MIN_GAMES_BETWEEN_PICKUPS) return;

            $fa = DB::table('players as p')
                ->leftJoin('player_season_stats as s', function ($j) {
                    $j->on('p.id', 's.player_id')
                      ->whereRaw('s.season_id = (
                          SELECT MAX(season_id)
                          FROM player_season_stats
                          WHERE player_id = p.id AND total_games_played > 0
                      )');
                })
                ->where([['p.team_id', 0], ['p.contract_years', 0], ['p.active', true], ['p.is_injured', false]])
                ->where('s.per', '>=', self::STAR_PER_THRESHOLD)
                ->orderByDesc('s.per')
                ->orderByDesc('s.eff')
                ->select('p.*', 's.per', 's.eff', 's.avg_points_per_game', 's.avg_assists_per_game', 's.ts_percent', 's.total_games_played', 's.avg_minutes_per_game', 's.role as season_role')
                ->first();

            if (!$fa) return;

            $weakest = DB::table('player_game_stats as g')
                ->join('players as p', 'g.player_id', 'p.id')
                ->leftJoin('player_season_stats as s', function ($j) use ($seasonId) {
                    $j->on('p.id', 's.player_id')->where('s.season_id', $seasonId);
                })
                ->select('p.id', 'p.name', DB::raw('COALESCE(s.per,0) as per'), DB::raw('SUM(g.minutes) as mins'))
                ->where('p.team_id', $teamId)
                ->groupBy('p.id', 'p.name', 's.per')
                ->orderBy('mins')
                ->limit(1)
                ->first();

            if (!$weakest) return;
            if (($fa->per - $weakest->per) < self::MIN_PER_GAP) return;

            if (!$this->isPlayerCompatibleWithTeam($teamId, $seasonId, $fa, $seasonPhase)) return;

            DB::transaction(function () use ($teamId, $roundId, $seasonId, $fa, $weakest) {
                DB::table('transactions')->insert([
                    'player_id'    => $weakest->id,
                    'season_id'    => $seasonId,
                    'details'      => 'Waived to sign ' . $fa->name,
                    'from_team_id' => $teamId,
                    'to_team_id'   => 0,
                    'status'       => 'waived',
                ]);
                DB::table('players')->where('id', $weakest->id)->update(['team_id' => 0, 'contract_years' => 0]);

                $years = $this->getContractYearsBasedOnRole($fa->season_role ?? 'starter');
                DB::table('players')->where('id', $fa->id)->update(['team_id' => $teamId, 'contract_years' => $years]);
                DB::table('transactions')->insert([
                    'player_id'    => $fa->id,
                    'season_id'    => $seasonId,
                    'details'      => 'Signed ' . $years . '-yr deal (replaced ' . $weakest->name . ')',
                    'from_team_id' => 0,
                    'to_team_id'   => $teamId,
                    'status'       => 'signed',
                ]);

                (new AwardsController)->storePlayerCurrentSeasonStats($teamId, $fa->id);
                if (self::ONE_PICKUP_PER_SIM_ROUND) {
                    Cache::put("waiver_done_T{$teamId}_R{$roundId}", true, now()->addDay());
                }
            });

        } catch (\Throwable $e) {
            \Log::error('Waiver error T' . $teamId . ': ' . $e->getMessage());
        }
    }

    private function isPlayerCompatibleWithTeam(int $teamId, int $seasonId, object $fa, int $phase): bool
    {
        $team = DB::table('player_season_stats as s')->join('players as p', 's.player_id', 'p.id')
            ->where('p.team_id', $teamId)->where('s.season_id', $seasonId)
            ->where('s.total_games_played', '>', 0)
            ->selectRaw('AVG(s.per) as per, AVG(s.ts_percent) as ts, AVG(s.avg_points_per_game) as pts, AVG(s.avg_assists_per_game) as ast')
            ->first();
        $teamPer = $team->per ?? 15;
        $teamTS = $team->ts ?? 0.5;
        $teamPts = $team->pts ?? 10;
        $teamAst = $team->ast ?? 2;

        $league = DB::table('player_season_stats')
            ->where('season_id', $seasonId)
            ->where('total_games_played', '>', 0)
            ->selectRaw('AVG(per) as per, AVG(ts_percent) as ts, AVG(avg_points_per_game) as pts, AVG(avg_assists_per_game) as ast')
            ->first();
        $lgPer = $league->per ?? 15;
        $lgTS = $league->ts ?? 0.5;
        $lgPts = $league->pts ?? 10;
        $lgAst = $league->ast ?? 2;

        $weakStat = null;
        if ($teamPts < $lgPts * 0.9) $weakStat = 'scoring';
        elseif ($teamAst < $lgAst * 0.9) $weakStat = 'playmaking';
        elseif ($teamTS < $lgTS * 0.9) $weakStat = 'efficiency';

        $faHelpsWeak = false;
        if ($weakStat === 'scoring' && $fa->avg_points_per_game > $teamPts) $faHelpsWeak = true;
        if ($weakStat === 'playmaking' && $fa->avg_assists_per_game > $teamAst) $faHelpsWeak = true;
        if ($weakStat === 'efficiency' && $fa->ts_percent > $teamTS) $faHelpsWeak = true;

        $perfUpgrade = ($fa->per > $teamPer + 1) || $faHelpsWeak;
        $score = $perfUpgrade ? self::W_PERFORMANCE : 0.0;

        $depth = DB::table('players')->where('team_id', $teamId)->where('position', $fa->position)->where('active', true)->where('is_injured', false)->count();
        if ($depth < self::MIN_HEALTHY_PER_POSITION) $score += self::W_POSITION_NEED;

        $starterCount = DB::table('players')->where('team_id', $teamId)->where('position', $fa->position)->where('role', 'starter')->count();
        $roleFit = ($fa->season_role === 'starter') ? ($starterCount < 2) : true;
        if ($roleFit) $score += self::W_ROLE_FIT;

        if (($fa->avg_minutes_per_game ?? 0) >= 15) $score += self::W_USAGE_RECENT;

        $isRebuilding = DB::table('standings_view')->where('season_id', $seasonId)->where('team_id', $teamId)->value('overall_rank') > 40;
        $ageFit = $isRebuilding ? ($fa->age <= 27) : ($fa->age >= 28);
        if ($ageFit) $score += self::W_AGE_FIT;

        $awards = DB::table('season_awards')->where('player_id', $fa->id)->whereIn('award_name', self::PRESTIGE_AWARDS)->count();
        if ($awards > 0) $score += min(self::W_AWARD_BONUS, 0.02 * $awards * 5);

        return $score >= 0.60 && mt_rand() / mt_getrandmax() <= self::RANDOM_PASS_THRESHOLD;
    }

    private function countTeamGamesSince(int $teamId, string $since): int
    {
        return DB::table('schedules')
            ->where(function ($q) use ($teamId) {
                $q->where('home_id', $teamId)->orWhere('away_id', $teamId);
            })
            ->where('status', '>=', 1)
            ->where('updated_at', '>', $since)
            ->count();
    }

    private function getCurrentSeasonId(): ?int
    {
        return DB::table('seasons')->where('status', '<=', 2)->latest('id')->value('id');
    }

    private function getContractYearsBasedOnRole(string $role): int
    {
        return match ($role) {
            'star player' => 4,
            'allstar'     => 3,
            'starter'     => 2,
            default       => 1,
        };
    }
}
