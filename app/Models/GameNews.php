<?
// app/Models/GameNews.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameNews extends Model
{
    protected $table = 'game_news';

    protected $fillable = [
        'game_id',
        'season_id',
        'home_team_id',
        'away_team_id',
        'winner_id',
        'headline',
        'body',
    ];
}
