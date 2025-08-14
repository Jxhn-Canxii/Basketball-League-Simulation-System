<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Player;
use App\Models\Game;

class PlayerGameStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'season_id',
        'game_id',
        'team_id',
        'is_injured',
        'role',
        'minutes',
        'points',
        'rebounds',
        'assists',
        'steals',
        'blocks',
        'turnovers',
        'fouls',

        // New Stats
        'field_goals_made',
        'field_goal_attempts',
        'two_pointers_made',
        'two_point_attempts',
        'three_pointers_made',
        'three_point_attempts',
        'free_throws_made',
        'free_throw_attempts',

        // Advanced Metrics
        'per',
        'ts_percent',
        'eff',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // Optionally, you can create methods to compute these advanced metrics dynamically if necessary
    // For example:
    
    // Method to calculate PER (Player Efficiency Rating)
    public function calculatePER()
    {
        // Calculate PER based on your formula
        if ($this->minutes == 0) {
            return 0;
        }
        return ($this->points + $this->rebounds + $this->assists + $this->steals + $this->blocks - ($this->total_field_goal_attempts - $this->total_field_goals_made) - $this->turnovers) / $this->minutes;
    }

    // Method to calculate TS% (True Shooting Percentage)
    public function calculateTSPercent()
    {
        // Calculate TS% based on your formula
        $denominator = $this->total_field_goal_attempts + (0.44 * $this->total_free_throw_attempts);
        if ($denominator == 0) {
            return 0;
        }
        return $this->points / (2 * $denominator);
    }

    // Method to calculate Efficiency (EFF)
    public function calculateEFF()
    {
        // Calculate Efficiency based on your formula
        $denominator = $this->total_field_goal_attempts + $this->total_free_throw_attempts + $this->turnovers;
        if ($denominator == 0) {
            return 0;
        }
        return ($this->points + $this->rebounds + $this->assists + $this->steals + $this->blocks - $denominator);
    }

    // Call these methods dynamically if you don't want to store them directly in the database
    public function saveAdvancedMetrics()
    {
        // Save the advanced metrics
        $this->per = $this->calculatePER();
        $this->ts_percent = $this->calculateTSPercent();
        $this->eff = $this->calculateEFF();

        $this->save();
    }
}
