<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class Start implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $game = $chat->game;

        if(!$game) {
            $bot->deleteMessage($chat->id, $update->getMessageId());
            return;
        }

        $user = $update->findUser();
        $game->start($bot, $user, $update->getId());
    }

}