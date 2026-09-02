<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->integer('player_id'); // the ID of the player
            $table->integer('season_id'); // the ID of the season
            $table->text('details'); // details of the transaction
            $table->integer('from_team_id'); // the team the player is leaving
            $table->integer('to_team_id')->default(0); // the team the player is joining (default 0 for waived)
            $table->string('status'); // the status of the transaction (e.g., "waived")
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
