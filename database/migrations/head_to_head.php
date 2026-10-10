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
        Schema::create('head_to_head', function (Blueprint $table) {
            $table->id();
            $table->integer('team_id')->default(0);
            $table->integer('opponent_id')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->integer('win_percentage')
            ->storedAs("
                CASE
                    WHEN (wins + losses) = 0 THEN 0
                    ELSE (wins / (wins + losses)) * 100
                END
            ");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('head_to_head');
    }
};



