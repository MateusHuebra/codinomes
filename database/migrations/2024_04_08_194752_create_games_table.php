<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['creating', 'master_a', 'master_b', 'agent_a', 'agent_b']);
            $table->timestamp('status_updated_at')->useCurrent();
            $table->tinyInteger('attempts_left')->nullable();
            $table->bigInteger('chat_id');
                $table->foreign('chat_id')->references('id')->on('chats');
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}