<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NewColorsToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('color_a', 6)->default('red')->change();
            $table->string('color_b', 6)->default('blue')->change();
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
            $table->enum('color_a', ['purple', 'orange', 'red', 'blue', 'green', 'yellow'])->default('purple')->change();
            $table->enum('color_b', ['purple', 'orange', 'red', 'blue', 'green', 'yellow'])->default('orange')->change();
        });
    }
}
