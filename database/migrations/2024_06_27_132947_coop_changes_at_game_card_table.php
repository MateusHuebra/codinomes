<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CoopChangesAtGameCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_cards', function (Blueprint $table) {
            $table->string('coop_team', 1)->nullable();
            $table->boolean('coop_revealed')->nullable();
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
            $table->dropColumn('coop_team');
            $table->dropColumn('coop_revealed');
        });
    }
}
