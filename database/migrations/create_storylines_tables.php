<?php
// database/migrations/[timestamp]_create_storylines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('storylines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id');
            $table->longText('storyline');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('storylines');
    }
};