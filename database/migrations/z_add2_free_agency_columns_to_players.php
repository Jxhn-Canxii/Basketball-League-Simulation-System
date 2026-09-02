<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (!Schema::hasColumn('players', 'is_restricted_fa')) {
                $table->boolean('is_restricted_fa')->default(false)->after('team_option');
            }

            if (!Schema::hasColumn('players', 'can_be_extended')) {
                $table->boolean('can_be_extended')->default(true)->after('is_restricted_fa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (Schema::hasColumn('players', 'can_be_extended')) {
                $table->dropColumn('can_be_extended');
            }

            if (Schema::hasColumn('players', 'is_restricted_fa')) {
                $table->dropColumn('is_restricted_fa');
            }
        });
    }
};
