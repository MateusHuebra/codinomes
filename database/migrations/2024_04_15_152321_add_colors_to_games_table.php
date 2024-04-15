<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorsToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->enum('color_a', ['purple', 'orange', 'red', 'blue', 'green', 'yellow'])->default('purple')->after('attempts_left');
            $table->enum('color_b', ['purple', 'orange', 'red', 'blue', 'green', 'yellow'])->default('orange')->after('color_a');
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
            $table->dropColumn('color_a');
            $table->dropColumn('color_b');
        });
    }
}
