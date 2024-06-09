<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Info implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
            return;
        }
        $chat = $update->findChat();
        if (!$chat) {
            return;
        }
        $game = $chat->currentGame();

        if ($game) {
            $text = AppString::get('mode.'.$game->mode.'_info');
        } else {
            $text = AppString::get('error.no_game');
        }
        
        $bot->sendMessage($chat->id, $text, null, false, $update->getMessageId(), null, false, null, null, true);
    }

}