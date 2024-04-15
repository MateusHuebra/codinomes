<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use TelegramBot\Api\BotApi;

class History implements Action {

    public function run($update, BotApi $bot) : Void {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $game = Game::where('chat_id', $chatId)->first();
        if(!$game) {
            return;
        }

        $bot->sendMessage($chatId, $game->history, null, false, $message->getMessageId(), null, false, null, null, true);
    }

}