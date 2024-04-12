<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Services\Telegram\BotApi;
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
        $game->stop();

        $bot->sendMessage($chatId, AppString::get('game.stopped'));
    }

}