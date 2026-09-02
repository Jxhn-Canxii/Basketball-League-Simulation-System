<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_players', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_players', 'draft_pick_right_id')) {
                $table->unsignedBigInteger('draft_pick_right_id')->nullable()->after('player_id');
                $table->index('draft_pick_right_id', 'idx_trade_players_draft_pick_right');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trade_players', function (Blueprint $table) {
            if (Schema::hasColumn('trade_players', 'draft_pick_right_id')) {
                $table->dropIndex('idx_trade_players_draft_pick_right');
                $table->dropColumn('draft_pick_right_id');
            }
        });
    }
};
