<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_caps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id')->unique();
            $table->decimal('salary_cap', 12, 2)->default(136000000);
            $table->decimal('luxury_tax_line', 12, 2)->default(165600000);
            $table->decimal('mle_value', 12, 2)->default(12400000);
            $table->decimal('vet_min_value', 12, 2)->default(1165720);
            $table->decimal('two_way_value', 12, 2)->default(578710);
            $table->timestamps();

            $table->index('season_id', 'idx_salary_caps_season');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_caps');
    }
};
