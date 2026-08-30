<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_players', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trade_proposal_id');

            $table->unsignedBigInteger('player_id');

            $table->unsignedBigInteger('from_team_id');
            $table->unsignedBigInteger('to_team_id');

            $table->string('player_name');
            $table->string('role', 100)->nullable();

            $table->timestamps();

            $table->index(
                'trade_proposal_id',
                'idx_trade_players_proposal'
            );

            $table->index(
                'player_id',
                'idx_trade_players_player'
            );

            $table->index(
                'from_team_id',
                'idx_trade_players_from_team'
            );

            $table->index(
                'to_team_id',
                'idx_trade_players_to_team'
            );

            $table->foreign('trade_proposal_id')
                ->references('id')
                ->on('trade_proposals')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_players');
    }
};