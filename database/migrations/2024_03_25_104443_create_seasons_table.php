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
            $table->foreignId('league_id')->constrained()->onDelete('cascade'); // foreign key for league_id
            $table->integer('type'); // enum for season type
            $table->integer('match_type'); // enum for match type
            $table->integer('start_playoffs'); // boolean flag to indicate if playoffs started
            $table->integer('is_conference'); // boolean to indicate if it's conference-related
            $table->integer('status'); // status of the season
            $table->foreignId('finals_mvp_id')->nullable()->constrained('players')->onDelete('set null'); // MVP player in finals
            $table->string('finals_mvp')->nullable(); // Name of the finals MVP
            $table->foreignId('finals_winner_id')->nullable()->constrained('teams')->onDelete('set null'); // Finals winner team
            $table->string('finals_winner_name')->nullable(); // Name of the finals winner team
            $table->integer('finals_winner_score')->nullable(); // Score of the finals winner
            $table->foreignId('finals_loser_id')->nullable()->constrained('teams')->onDelete('set null'); // Finals loser team
            $table->string('finals_loser_name')->nullable(); // Name of the finals loser team
            $table->integer('finals_loser_score')->nullable(); // Score of the finals loser
            $table->foreignId('west_champion_id')->nullable()->constrained('teams')->onDelete('set null'); // Western champion team
            $table->string('west_champion_name')->nullable(); // Name of the western champion team
            $table->foreignId('east_champion_id')->nullable()->constrained('teams')->onDelete('set null'); // Eastern champion team
            $table->string('east_champion_name')->nullable(); // Name of the eastern champion team
            $table->foreignId('north_champion_id')->nullable()->constrained('teams')->onDelete('set null'); // Northern champion team
            $table->string('north_champion_name')->nullable(); // Name of the northern champion team
            $table->foreignId('south_champion_id')->nullable()->constrained('teams')->onDelete('set null'); // Southern champion team
            $table->string('south_champion_name')->nullable(); // Name of the southern champion team
            $table->foreignId('champion_id')->nullable()->constrained('teams')->onDelete('set null'); // Champion team
            $table->string('champion_name')->nullable(); // Name of the champion team
            $table->foreignId('weakest_id')->nullable()->constrained('teams')->onDelete('set null'); // Weakest team
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
