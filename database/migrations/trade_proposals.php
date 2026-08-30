<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_proposals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');

            $table->enum('type', [
                'in-season',
                'off-season',
            ]);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->unsignedTinyInteger('team_count')->default(2);

            $table->timestamps();

            $table->index(
                ['season_id', 'status'],
                'idx_trade_proposals_season_status'
            );

            $table->index(
                ['season_id', 'type'],
                'idx_trade_proposals_season_type'
            );

            $table->index(
                'status',
                'idx_trade_proposals_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_proposals');
    }
};