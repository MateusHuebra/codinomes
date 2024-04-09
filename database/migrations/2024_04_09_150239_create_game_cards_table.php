<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_cards', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained();
            $table->tinyInteger('id');
            $table->string('text', 12);
            $table->enum('team', ['a', 'b', 'w', 'x']);
            $table->boolean('revealed');

            $table->primary(['game_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_cards');
    }
}
