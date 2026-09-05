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
        Schema::create('schedules_archives', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->string('game_number');
            $table->string('round');
            $table->integer('season_id')->constrained()->onDelete('cascade');
            $table->integer('conference_id');
            $table->string('series_id');
            $table->integer('home_id')->constrained('teams')->onDelete('cascade');
            $table->integer('home_score')->default(0);
            $table->integer('away_id')->constrained('teams')->onDelete('cascade');
            $table->integer('away_score')->default(0);
            $table->integer('winner_id')->default(0);
            $table->integer('status')->default(1); // Assuming default status is 'pending'
            $table->integer('is_overtime')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules_archives');
    }
};
