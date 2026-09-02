<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (!Schema::hasColumn('players', 'salary')) {
                $table->decimal('salary', 12, 2)->default(0)->after('contract_years');
            }

            if (!Schema::hasColumn('players', 'contract_type')) {
                $table->string('contract_type', 50)->nullable()->after('salary');
            }

            if (!Schema::hasColumn('players', 'no_trade_clause')) {
                $table->boolean('no_trade_clause')->default(false)->after('contract_type');
            }

            if (!Schema::hasColumn('players', 'player_option')) {
                $table->boolean('player_option')->default(false)->after('no_trade_clause');
            }

            if (!Schema::hasColumn('players', 'team_option')) {
                $table->boolean('team_option')->default(false)->after('player_option');
            }
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $columns = [
                'can_be_extended',
                'is_restricted_fa',
                'team_option',
                'player_option',
                'no_trade_clause',
                'contract_type',
                'salary',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('players', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
