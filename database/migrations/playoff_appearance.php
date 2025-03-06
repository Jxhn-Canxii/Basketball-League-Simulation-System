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
        Schema::create('player_playoff_appearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            
            // Play-in Tournament Stats
            $table->integer('play_ins_elims_round_1_appearances')->default(0);
            $table->integer('play_ins_elims_round_2_appearances')->default(0);
            $table->integer('play_ins_finals_appearances')->default(0);
            
            // Playoff Round Stats
            $table->integer('round_of_32_appearances')->default(0);
            $table->integer('round_of_16_appearances')->default(0);
            $table->integer('quarter_finals_appearances')->default(0);
            $table->integer('semi_finals_appearances')->default(0);
            $table->integer('interconference_semi_finals_appearances')->default(0);
            $table->integer('finals_appearances')->default(0);
            
            // Career Totals
            $table->integer('total_playoff_appearances')->default(0);
            $table->integer('seasons_played_in_playoffs')->default(0);
            $table->integer('total_seasons_played')->default(0);
            $table->integer('championships_won')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_playoff_appearances');
    }
};
