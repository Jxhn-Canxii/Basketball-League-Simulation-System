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
        Schema::create('player_contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id');
            $table->integer('season_id');
            $table->integer('team_id');
            $table->decimal('salary',10,2)->nullable(0);
            $table->integer('contract_years');
            $table->string('contract_type');
            $table->boolean('player_option');
            $table->boolean('team_option');
            $table->boolean('no_trade_clause');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_contracts');
    }
};
