<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key for the id
            $table->string('name'); // name of the season
            $table->integer('league_id'); // foreign key for league_id
            $table->integer('type'); // enum for season type
            $table->integer('match_type'); // enum for match type
            $table->integer('start_playoffs'); // boolean flag to indicate if playoffs started
            $table->integer('is_conference'); // boolean to indicate if it's conference-related
            $table->integer('status'); // status of the season
            $table->integer('finals_mvp_id')->nullable(); // MVP player in finals
            $table->string('finals_mvp')->nullable(); // Name of the finals MVP
            $table->integer('finals_winner_id')->nullable(); // Finals winner team
            $table->string('finals_winner_name')->nullable(); // Name of the finals winner team
            $table->integer('finals_winner_score')->nullable(); // Score of the finals winner
            $table->integer('finals_loser_id')->nullable(); // Finals loser team
            $table->string('finals_loser_name')->nullable(); // Name of the finals loser team
            $table->integer('finals_loser_score')->nullable(); // Score of the finals loser
            $table->integer('west_champion_id')->nullable(); // Western champion team
            $table->string('west_champion_name')->nullable(); // Name of the western champion team
            $table->integer('east_champion_id')->nullable(); // Eastern champion team
            $table->string('east_champion_name')->nullable(); // Name of the eastern champion team
            $table->integer('north_champion_id')->nullable(); // Northern champion team
            $table->string('north_champion_name')->nullable(); // Name of the northern champion team
            $table->integer('south_champion_id')->nullable(); // Southern champion team
            $table->string('south_champion_name')->nullable(); // Name of the southern champion team
            $table->integer('champion_id')->nullable(); // Champion team
            $table->string('champion_name')->nullable(); // Name of the champion team
            $table->integer('weakest_id')->nullable(); // Weakest team
            $table->string('weakest_name')->nullable(); // Name of the weakest team
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
