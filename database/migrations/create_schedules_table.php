<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->string('game_id');

            $table->string('round');
            $table->integer('season_id');
            $table->integer('conference_id');
            $table->integer('series_id');

            $table->integer('home_id');
            $table->integer('home_score')->default(0);

            $table->integer('away_id');
            $table->integer('away_score')->default(0);

            $table->integer('winner_id')->default(0);
            $table->integer('status')->default(1);

            $table->timestamps();
        });

        /*
         * Add game_number after game_id.
         *
         * Example:
         * game_id = "2026-ROUND1-15"
         * game_number = 15
         */
        DB::statement('
            ALTER TABLE schedules
            ADD game_number INT
            GENERATED ALWAYS AS (
                CAST(
                    SUBSTRING_INDEX(game_id, "-", -1)
                    AS UNSIGNED
                )
            ) STORED
            AFTER game_id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};