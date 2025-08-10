<?
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playoff_series', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('season_id');
            $table->string('conference_id', 50)->nullable();
            $table->string('round', 50);
            $table->string('series_id', 255)->nullable();
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            $table->integer('best_of')->default(7);
            $table->integer('home_wins')->default(0);
            $table->integer('away_wins')->default(0);
            $table->integer('series_length')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->unsignedBigInteger('winner_team_id')->nullable();
            $table->unsignedBigInteger('loser_team_id')->nullable();
            $table->timestamps();

            // Optional foreign keys (uncomment if related tables exist)
            // $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            // $table->foreign('home_team_id')->references('id')->on('teams')->onDelete('cascade');
            // $table->foreign('away_team_id')->references('id')->on('teams')->onDelete('cascade');
            // $table->foreign('winner_team_id')->references('id')->on('teams')->onDelete('set null');
            // $table->foreign('loser_team_id')->references('id')->on('teams')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playoff_series');
    }
};
