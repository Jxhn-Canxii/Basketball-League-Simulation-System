Schema::create('game_play_by_play', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('game_id');
    $table->unsignedBigInteger('season_id');

    $table->unsignedBigInteger('team_id');
    $table->unsignedBigInteger('player_id')->nullable();

    $table->unsignedBigInteger('opponent_team_id')->nullable();
    $table->unsignedBigInteger('secondary_player_id')->nullable();

    $table->integer('quarter');
    $table->integer('possession_number');

    $table->integer('game_clock_seconds');

    $table->string('event_type');
    $table->integer('points')->default(0);

    $table->integer('shot_value')->nullable();
    $table->boolean('shot_made')->nullable();

    $table->text('description')->nullable();

    $table->timestamps();

    $table->index(['game_id', 'quarter', 'possession_number']);
    $table->index(['game_id', 'team_id']);
    $table->index(['game_id', 'player_id']);
});