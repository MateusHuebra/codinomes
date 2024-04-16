<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class Stop implements Action {

    public function run($update, BotApi $bot) : Void {
        $chatId = $update->getMessage()->getChat()->getId();
        $game = Game::where('chat_id', $chatId)->first();
        if(!$game) {
            return;
        }
        if(!$game->chat->isTgUserAdmin($update->getMessage()->getFrom(), $bot)) {
            $bot->sendMessage($chatId, AppString::get('error.admin_only'), null, false, null, null, false, null, null, true);
            return;
        }

        $game->stop();

        $bot->sendMessage($chatId, AppString::get('game.stopped'));
    }

}