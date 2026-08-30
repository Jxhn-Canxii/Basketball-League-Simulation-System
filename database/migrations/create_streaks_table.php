<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('streak', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key for the id
            $table->integer('team_id'); // foreign key for team_id
            $table->unsignedInteger('best_winning_streak'); // Best winning streak
            $table->unsignedInteger('best_losing_streak'); // Best losing streak
            $table->integer('best_winning_streak_start_id'); // Foreign key for the start game of the best winning streak
            $table->integer('best_winning_streak_end_id'); // Foreign key for the end game of the best winning streak
            $table->integer('best_losing_streak_start_id'); // Foreign key for the start game of the best losing streak
            $table->integer('best_losing_streak_end_id'); // Foreign key for the end game of the best losing streak
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('streak');
    }
};
