<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTeamVarsToStringOnGameCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_cards', function (Blueprint $table) {
            $table->string('team', 1)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_cards', function (Blueprint $table) {
            $table->enum('team', ['a', 'b', 'w', 'x'])->change();
        });
    }
}
