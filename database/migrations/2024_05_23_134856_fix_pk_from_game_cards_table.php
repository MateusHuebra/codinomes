<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixPkFromGameCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('game_cards');
        
        Schema::create('game_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained();
            $table->tinyInteger('position');
            $table->string('text', 16);
            $table->enum('team', ['a', 'b', 'w', 'x']);
            $table->boolean('revealed');

            $table->unique(['game_id', 'position']);
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
        
        Schema::create('game_cards', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained();
            $table->tinyInteger('id');
            $table->string('text', 16);
            $table->enum('team', ['a', 'b', 'w', 'x']);
            $table->boolean('revealed');

            $table->primary(['game_id', 'id']);
        });
    }
}
