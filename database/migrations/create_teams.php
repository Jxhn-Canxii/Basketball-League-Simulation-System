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
            $table->id(); // auto-incrementing primary key for the id
            $table->string('name'); // Team name
            $table->string('acronym', 10); // Team acronym, max length of 10
            $table->string('primary_color', 8); // Primary color of the team (HEX color code)
            $table->string('secondary_color', 8); // Secondary color of the team (HEX color code)
            $table->integer('league_id'); // Foreign key for league_id, referencing the 'leagues' table
            $table->integer('conference_id'); // Foreign key for conference_id, referencing the 'conferences' table
            $table->timestamps(); // created_at, updated_at
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
