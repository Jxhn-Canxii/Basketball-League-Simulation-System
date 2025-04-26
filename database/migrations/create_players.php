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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('country', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->integer('contract_years')->default(0);
            $table->integer('hardship_contract')->default(0);
            $table->date('contract_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_rookie')->default(false);
            $table->integer('age');
            $table->integer('retirement_age')->default(35);

            // New Position Column
            $table->string('position', 10)->nullable();

            $table->string('role', 100)->nullable();

            // Ratings
            $table->decimal('shooting_rating', 5, 2)->default(0);
            $table->decimal('two_point_rating', 5, 2)->default(0);
            $table->decimal('three_point_rating', 5, 2)->default(0);
            $table->decimal('free_throw_rating', 5, 2)->default(0);
            $table->decimal('defense_rating', 5, 2)->default(0);
            $table->decimal('passing_rating', 5, 2)->default(0);
            $table->decimal('rebounding_rating', 5, 2)->default(0);
            $table->decimal('athleticism_rating', 5, 2)->default(0);
            $table->decimal('basketball_iq_rating', 5, 2)->default(0);
            $table->decimal('strength_rating', 5, 2)->default(0);
            $table->decimal('stamina_rating', 5, 2)->default(0);
            $table->decimal('clutch_rating', 5, 2)->default(0);
            $table->decimal('leadership_rating', 5, 2)->default(0);
            $table->decimal('work_ethic_rating', 5, 2)->default(0);
            $table->decimal('overall_rating', 5, 2)->default(0);
            $table->string('type', 50)->nullable();

            // Draft Information
            $table->unsignedBigInteger('draft_id')->nullable();
            $table->integer('draft_order')->nullable();
            $table->unsignedBigInteger('drafted_team_id')->nullable();
            $table->boolean('is_drafted')->default(false);
            $table->string('draft_status', 255)->nullable();

            // Injury & Fatigue
            $table->decimal('injury_prone_percentage', 5, 2)->default(0);
            $table->boolean('is_injured')->default(false);
            $table->string('injury_type', 255)->nullable();
            $table->decimal('fatigue', 5, 2)->default(0);
            $table->text('injury_history')->nullable();
            $table->decimal('injury_recovery_games',5,2)->default(0);
            $table->integer('morale')->default(75);
            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
