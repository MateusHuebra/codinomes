<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameGhostToMysteryOnGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            DB::table('games')
                ->where('mode', 'ghost')
                ->update(['mode' => 'mystery']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $table) {
            DB::table('games')
                ->where('mode', 'mystery')
                ->update(['mode' => 'ghost']);
        });
    }
}
