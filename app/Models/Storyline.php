<?php

// app/Models/Storyline.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storyline extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'story_line'
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}