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
        // Check if the table exists first
        if (!Schema::hasTable('player_ratings')) {
            Schema::create('player_ratings', function (Blueprint $table) {
                $table->id();
                $table->integer('player_id')
                      ->constrained('players')
                      ->onDelete('cascade');
                $table->integer('season_id')
                      ->constrained('seasons')
                      ->onDelete('cascade');
                $table->enum('role', ['star player', 'all star', 'starter', 'role player', 'bench']);
                $table->integer('shooting_rating');
                $table->integer('defense_rating');
                $table->integer('passing_rating');
                $table->integer('rebounding_rating');
                $table->integer('overall_rating');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_ratings');
    }
};
