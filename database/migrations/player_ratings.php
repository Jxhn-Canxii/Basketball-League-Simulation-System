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
        Schema::create('player_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('season_id')->constrained();
            $table->enum('role', ['star player','all star', 'starter', 'role player', 'bench']);
            $table->unsignedTinyInteger('shooting_rating');
            $table->unsignedTinyInteger('defense_rating');
            $table->unsignedTinyInteger('passing_rating');
            $table->unsignedTinyInteger('rebounding_rating');
            $table->unsignedTinyInteger('overall_rating');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_ratings');
    }
};
