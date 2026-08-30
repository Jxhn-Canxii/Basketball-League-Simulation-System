<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->integer('team_id');
            $table->integer('player_id');
            $table->integer('season_id');
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
