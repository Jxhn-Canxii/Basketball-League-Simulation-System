<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeasonTeamChemistryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('season_team_chemistry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->integer('chemistry')->default(75); // Default chemistry set to 75
            $table->timestamps();

            // Foreign key constraints (adjust these if you have other naming conventions)
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');

            // Ensure combination of team_id and season_id is unique
            $table->unique(['team_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('season_team_chemistry');
    }
}
