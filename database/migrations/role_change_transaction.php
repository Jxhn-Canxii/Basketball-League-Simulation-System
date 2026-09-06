<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('role_change_transaction', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->integer('player_id'); // the ID of the player
            $table->integer('season_id'); // the ID of the season
            $table->text('details'); // details of the transaction
            $table->integer('team_id'); // the team the player is leaving
            $table->string('status'); // the status of the transaction (e.g., "waived")
            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_change_transaction');
    }
};
