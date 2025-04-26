<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamSeasonInfo extends Model
{
    use HasFactory;

    protected $table = 'team_season_info';

    protected $fillable = [
        'team_id',
        'season_id',
        'conference_id',
        'coach_id',
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function conference()
    {
        return $this->belongsTo(Conference::class);
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }
}
