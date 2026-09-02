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
        Schema::create('teams', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Team name
            $table->string('acronym', 8); // Team acronym (e.g., LAL, GSW)
            $table->string('primary_color', 8); // HEX color code (e.g., #FF0000)
            $table->string('secondary_color', 8); // HEX color code
            $table->unsignedBigInteger('league_id'); // Foreign key for league_id
            $table->unsignedBigInteger('conference_id'); // Foreign key for league_id
            $table->unsignedBigInteger('coach_id'); // Foreign key for league_id
            $table->longText('description')->nullable(); // Added long text column
            $table->string('sponsor',255)->nullable(); // Added ong text column for sponsor
            $table->string('city',255)->nullable(); // Added long text column for city
            $table->integer('market_size'); // Added integer column for market size
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};