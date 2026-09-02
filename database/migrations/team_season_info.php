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
        Schema::create('team_season_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('conference_id');
            $table->unsignedBigInteger('coach_id');
            $table->integer('coach_iq');
            $table->integer('chemistry');
            $table->integer('is_playoff_qualified');
            $table->integer('is_defending_champion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_season_info');
    }
};
