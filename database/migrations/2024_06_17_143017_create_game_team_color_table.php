<?php

use App\Models\Game;
use App\Models\GameTeamColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameTeamColorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_team_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained();
            $table->string('team', 1);
            $table->string('color', 6);
        });

        foreach(Game::all() as $game) {
            GameTeamColor::create([
                'game_id' => $game->id,
                'team' => 'a',
                'color' => $game->color_a
            ]);
            GameTeamColor::create([
                'game_id' => $game->id,
                'team' => 'b',
                'color' => $game->color_b
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_team_colors');
    }
}
