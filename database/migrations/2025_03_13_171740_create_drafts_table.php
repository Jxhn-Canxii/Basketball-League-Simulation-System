<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->integer('round');
            $table->integer('pick_number');
            $table->string('draft_status', 255);
            $table->timestamps(); // Adds created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('drafts');
    }
};
