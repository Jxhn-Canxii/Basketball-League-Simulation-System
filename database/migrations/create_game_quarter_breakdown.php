<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_quarter_breakdown', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->integer('season_id')->default(0);
            $table->integer('team_id')->default(0);
            $table->integer('Q1')->default(0);
            $table->integer('Q2')->default(0);
            $table->integer('Q3')->default(0);
            $table->integer('Q4')->default(0);
            $table->integer('OT1')->default(0);
            $table->integer('OT2')->default(0);
            $table->integer('OT3')->default(0);
            $table->storedAs("Q1 + Q2 + Q3 + Q4 + OT1 + OT2 + OT3");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_quarter_breakdown');
    }
};