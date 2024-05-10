<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\ReplyKeyboardRemove;

class Stop implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $user = $update->findUser();
        $game = $chat->game;

        if(!$game || !$user) {
            return;
        }
        if(!$game->hasPermission($user, $bot)) {
            $bot->sendMessage($chat->id, AppString::get('error.admin_only'), null, false, null, null, false, null, null, true);
            return;
        }
        
        $game->stop($bot, true);

        $bot->sendMessage($chat->id, AppString::get('game.stopped'), null, false, null, new ReplyKeyboardRemove);
    }

}