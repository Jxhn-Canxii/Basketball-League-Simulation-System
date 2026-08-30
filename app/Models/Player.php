<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Player extends Model
{
    use HasFactory;

    protected $table = 'players';

    protected $fillable = [
        'name',
        'country',
        'address',
        'team_id',
        'contract_years',
        'hardship_contract',
        'contract_expires_at',
        'is_active',
        'is_rookie',
        'age',
        'retirement_age',
        'injury_prone_percentage',
        'role',
        'position', // Newly added position field
        'type',
        'shooting_rating',
        'three_point_rating',
        'two_point_rating',
        'free_throw_rating',
        'defense_rating',
        'passing_rating',
        'rebounding_rating',
        'athleticism_rating', // New
        'basketball_iq_rating', // New
        'strength_rating', // New
        'stamina_rating', // New
        'clutch_rating', // New
        'leadership_rating', // New
        'work_ethic_rating', // New
        'overall_rating',
        'potential_rating',
        'loyalty_rating',
        'satisfaction_rating',
        'ambition_rating',
        'negotation_skill_rating',
        'draft_id',
        'draft_order',
        'drafted_team_id',
        'is_drafted',
        'draft_status',
        'fatigue',
        'is_injured',
        'injury_status',
        'injury_history',
        'injury_recovery_games',
        'morale',
    ];

    protected $hidden = [];

    protected $casts = [
        'contract_expires_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Teams::class);
    }

    public function playerGameStats()
    {
        return $this->hasMany(PlayerGameStats::class);
    }
}
