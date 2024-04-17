<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatPackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_pack', function (Blueprint $table) {
            $table->bigInteger('chat_id');
                $table->foreign('chat_id')->references('id')->on('chats');
            $table->foreignId('pack_id')->constrained();

            $table->primary(['chat_id', 'pack_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_pack');
    }
}
