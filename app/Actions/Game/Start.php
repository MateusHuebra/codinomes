<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class Start implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $game = $chat->currentGame();

        if(!$game) {
            $bot->deleteMessage($chat->id, $update->getMessageId());
            return;
        }
        if(!in_array($game->status, ['creating', 'lobby'])) {
            return;
        }
        if($game->cards()->exists()) {
            return;
        }

        $user = $update->findUser();
        $game->start($bot, $user, $update->getId());
    }

}