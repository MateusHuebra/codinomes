<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
                $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedSmallInteger('games_as_master')->default(0);
            $table->unsignedSmallInteger('games_as_agent')->default(0);

            $table->unsignedSmallInteger('wins_as_master')->default(0);
            $table->unsignedSmallInteger('wins_as_agent')->default(0);

            $table->unsignedSmallInteger('attempts_on_ally_streak')->default(0);
            $table->unsignedSmallInteger('attempts_on_ally')->default(0);
            $table->unsignedSmallInteger('attempts_on_opponent')->default(0);
            $table->unsignedSmallInteger('attempts_on_black')->default(0);
            $table->unsignedSmallInteger('attempts_on_white')->default(0);

            $table->unsignedSmallInteger('hinted_to_ally_streak')->default(0);
            $table->unsignedSmallInteger('hinted_to_ally')->default(0);
            $table->unsignedSmallInteger('hinted_to_opponent')->default(0);
            $table->unsignedSmallInteger('hinted_to_black')->default(0);
            $table->unsignedSmallInteger('hinted_to_white')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_stats');
    }
}
