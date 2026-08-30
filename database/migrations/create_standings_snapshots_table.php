<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('standings_snapshots', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('team_id')->index();
            $table->string('team_name', 100);
            $table->string('team_city', 100);
            $table->string('primary_color', 7)->nullable(); // e.g., #FF0000 for hex color
            $table->string('secondary_color', 7)->nullable(); // e.g., #00FF00
            $table->string('team_acronym', 10);
            $table->unsignedBigInteger('conference_id')->index();
            $table->string('conference_name', 100);
            $table->unsignedBigInteger('season_id')->index();
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('total_home_score')->default(0);
            $table->unsignedInteger('total_away_score')->default(0);
            $table->decimal('home_ppg', 5, 2)->nullable(); // Points per game, e.g., 100.50
            $table->decimal('away_ppg', 5, 2)->nullable(); // Points per game, e.g., 98.75
            $table->integer('score_difference')->default(0); // Can be positive or negative
            $table->unsignedInteger('conference_rank');
            $table->unsignedInteger('overall_rank');
            $table->boolean('is_defending_champion')->default(false);
            $table->string('chemistry', 50)->nullable(); // Assuming chemistry is a string (e.g., 'Good', 'Average')
            $table->string('last_playoff_season_name', 100)->default('');
            $table->unsignedInteger('playoff_appearances')->default(0);
            $table->unsignedInteger('finals_appearances')->default(0);
            $table->unsignedInteger('conference_finals_appearances')->default(0);
            $table->unsignedInteger('conference_championships')->default(0);
            $table->unsignedInteger('championships')->default(0);
            $table->string('streak_status', 10)->nullable(); // e.g., 'W3', 'L2'
            $table->string('last_5_games', 5)->default(''); // e.g., 'WWLWL'
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings_snapshots');
    }
};