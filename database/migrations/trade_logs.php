<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');

            /*
             * Nullable because trade logs are historical records.
             * The proposal can be removed/archived without
             * deleting the trade history.
             */
            $table->unsignedBigInteger('trade_proposal_id')
                ->nullable();

            $table->unsignedBigInteger('team_from_id');
            $table->unsignedBigInteger('team_to_id');

            $table->unsignedBigInteger('player_id');

            $table->string('player_name');

            $table->string('role', 100)
                ->nullable();

            $table->text('trade_reason')
                ->nullable();

            $table->timestamps();

            $table->index(
                'season_id',
                'idx_trade_logs_season'
            );

            $table->index(
                'trade_proposal_id',
                'idx_trade_logs_proposal'
            );

            $table->index(
                'player_id',
                'idx_trade_logs_player'
            );

            $table->index(
                'team_from_id',
                'idx_trade_logs_team_from'
            );

            $table->index(
                'team_to_id',
                'idx_trade_logs_team_to'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_logs');
    }
};