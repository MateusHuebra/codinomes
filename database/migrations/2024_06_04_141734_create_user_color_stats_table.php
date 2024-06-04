<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserColorStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_color_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users');
            $table->string('color', 6);
            
            $table->unsignedSmallInteger('games_as_master')->default(0);
            $table->unsignedSmallInteger('games_as_agent')->default(0);

            $table->unsignedSmallInteger('wins_as_master')->default(0);
            $table->unsignedSmallInteger('wins_as_agent')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_color_stats');
    }
}
