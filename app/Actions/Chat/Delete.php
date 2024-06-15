<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class Delete implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $chat->actived = false;
        $chat->save();
    }

}