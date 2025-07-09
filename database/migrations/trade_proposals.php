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
        Schema::create('trade_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id');
            $table->foreignId('team_to_id');
            $table->foreignId('team_from_id');
            $table->foreignId('player_from_id');
            $table->foreignId('player_to_id');
            $table->string('type', 20);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_proposals');
    }
};
