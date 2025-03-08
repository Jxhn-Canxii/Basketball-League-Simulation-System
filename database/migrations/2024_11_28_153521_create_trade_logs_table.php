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
        Schema::create('trade_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_from_id')->integer();
            $table->foreignId('team_to_id')->integer();
            $table->foreignId('player_id')->integer();
            $table->string('player_name');
            $table->string('role', 50);
            $table->text('trade_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_logs');
    }
};
