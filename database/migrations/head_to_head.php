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
        Schema::create('head_to_head', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained();
            $table->foreignId('opponent_id')->constrained('teams');
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->decimal('win_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            // Composite primary key
            $table->primary(['team_id', 'opponent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('head_to_head');
    }
};



