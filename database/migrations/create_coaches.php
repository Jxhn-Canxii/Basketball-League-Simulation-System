s<?php

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
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->integer('retirement_age');
            $table->string('nationality', 100)->nullable();
            $table->integer('team_id')->default(0);
            $table->enum('coaching_style', ['defensive', 'offensive', 'balanced', 'fast-paced', 'slow-tempo']);
            $table->integer('coach_iq');
            $table->integer('experience_years');
            $table->integer('offensive_rating')->check('offensive_rating >= 1 AND offensive_rating <= 100');
            $table->integer('defensive_rating')->check('defensive_rating >= 1 AND defensive_rating <= 100');
            $table->integer('development_rating')->check('development_rating >= 1 AND development_rating <= 100');
            $table->integer('leadership_rating')->check('leadership_rating >= 1 AND leadership_rating <= 100');
            $table->integer('strategy_rating')->check('strategy_rating >= 1 AND strategy_rating <= 100');
            $table->integer('work_ethic_rating')->check('work_ethic_rating >= 1 AND work_ethic_rating <= 100');
            $table->string('preferred_team_composition')->default('any');
            $table->boolean('is_active')->default(true);
            $table->integer('career_wins')->default(0);
            $table->integer('career_losses')->default(0);
            $table->decimal('winning_percentage', 5, 2)
            ->storedAs("
                CASE
                    WHEN (career_wins + career_losses) = 0 THEN 0
                    ELSE (career_wins / (career_wins + career_losses)) * 100
                END
            ");
            $table->integer('contract_years')->default(0);
            $table->date('contract_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
