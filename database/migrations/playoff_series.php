<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playoff_series', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('season_id');
            $table->string('conference_id', 50)->nullable();
            $table->string('round', 50);
            $table->string('series_id', 255)->nullable();
            $table->integer('home_team_id')->default(0);;
            $table->integer('away_team_id')->default(0);;
            $table->integer('race_to')->default(7);
            $table->integer('home_wins')->default(0);
            $table->integer('away_wins')->default(0);
            $table->integer('series_length')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->integer('winner_team_id')->default(0);
            $table->integer('loser_team_id')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playoff_series');
    }
};
