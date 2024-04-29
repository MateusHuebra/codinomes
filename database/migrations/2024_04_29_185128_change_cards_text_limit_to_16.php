<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCardsTextLimitTo16 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('text', 16)->change();
        });
        Schema::table('game_cards', function (Blueprint $table) {
            $table->string('text', 16)->change();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('text', 12)->change();
        });
        Schema::table('game_cards', function (Blueprint $table) {
            $table->string('text', 12)->change();
        });
    }
}
