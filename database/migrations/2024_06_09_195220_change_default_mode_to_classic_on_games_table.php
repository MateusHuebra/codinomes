<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDefaultModeToClassicOnGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('games')
            ->where('mode', 'default')
            ->update(['mode' => 'classic']);

        Schema::table('games', function (Blueprint $table) {
            $table->string('mode', 8)->default('classic')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('games')
            ->where('mode', 'classic')
            ->update(['mode' => 'default']);

        Schema::table('games', function (Blueprint $table) {
            $table->string('mode', 8)->default('default')->change();
        });
    }
}
