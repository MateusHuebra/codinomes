<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class Start implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('private')) {
            $user = $update->findUser();

        } else if($update->isChatType('supergroup')) {
            $chat = $update->findChat();

        } else {
            return;
        }
        
        $game = ($chat??$user)->currentGame();
        if(!$game) {
            $bot->deleteMessage($update->getChatId(), $update->getMessageId());
            return;
        }
        if(!in_array($game->status, ['creating', 'lobby'])) {
            return;
        }
        if($game->cards()->exists()) {
            return;
        }

        $game->start($bot, $user, $update->getId());
    }

}