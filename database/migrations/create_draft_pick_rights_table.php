<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draft_pick_rights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id');
            $table->unsignedTinyInteger('round');
            $table->unsignedBigInteger('original_team_id');
            $table->unsignedBigInteger('current_owner_id');
            $table->boolean('is_traded')->default(false);
            $table->unsignedBigInteger('trade_proposal_id')->nullable();
            $table->string('protections', 255)->nullable();
            $table->timestamps();

            $table->index(['season_id', 'round'], 'idx_draft_pick_rights_season_round');
            $table->index('current_owner_id', 'idx_draft_pick_rights_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_pick_rights');
    }
};
