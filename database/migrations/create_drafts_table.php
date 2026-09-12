<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();

            // Draft information
            $table->unsignedBigInteger('original_team_id')->default(0);
            $table->unsignedBigInteger('team_id')->default(0);
            $table->unsignedBigInteger('player_id')->default(0);
            $table->unsignedBigInteger('draft_pick_right_id')->nullable();
            $table->unsignedBigInteger('season_id');
            $table->unsignedTinyInteger('round');
            $table->unsignedInteger('pick_number');
            $table->string('draft_status', 255);

            // Decision information
            $table->string('decision_maker_type', 20)->nullable();
            $table->string('decision_maker_name', 150)->nullable();
            $table->longText('decision_maker_reason')->nullable();
            $table->json('decision_factors')->nullable();
            $table->decimal('draft_score', 10, 2)->nullable();

            // Signing information
            $table->boolean('signed')->default(false);
            $table->unsignedTinyInteger('contract_years')->nullable();
            $table->decimal('contract_salary', 15, 2)->nullable();
            $table->string('contract_type', 100)->nullable();

            // Roster / waiver information
            $table->unsignedBigInteger('waived_player_id')->nullable();

            // Decision timestamp
            $table->timestamp('decision_at')->nullable();

            $table->timestamps();

            // Useful indexes
            $table->index(
                ['season_id', 'round', 'pick_number'],
                'drafts_season_round_pick_index'
            );

            $table->index(
                ['season_id', 'player_id'],
                'drafts_season_player_index'
            );

            $table->index(
                ['season_id', 'team_id'],
                'drafts_season_team_index'
            );

            $table->index(
                'draft_pick_right_id',
                'drafts_pick_right_index'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('drafts');
    }
};