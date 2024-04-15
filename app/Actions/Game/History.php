<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class History implements Action {

    public function run($update, BotApi $bot) : Void {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $game = Game::where('chat_id', $chatId)->first();
        if(!$game) {
            return;
        }

        $bot->sendMessage($chatId, $game->history??AppString::get('error.no_history'), null, false, $message->getMessageId(), null, false, null, null, true);
    }

}