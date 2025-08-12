Schema::create('player_series_appearances', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('player_id');
    $table->string('series_identifier');
    $table->unsignedBigInteger('season_id');
    $table->string('round');
    $table->timestamp('created_at')->useCurrent();
    $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
    $table->unique(['player_id', 'series_identifier']);
});