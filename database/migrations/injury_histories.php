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
        Schema::create('injury_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('game_id',255);
            $table->foreignId('team_id');
            $table->foreignId('season_id');
            $table->string('injury_type');
            $table->integer('recovery_games');
            $table->decimal('performance_impact', 3, 2);
            $table->timestamp('injury_date')->useCurrent();
            $table->timestamp('recovery_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('injury_histories');
    }
};
