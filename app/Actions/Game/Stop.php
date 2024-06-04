<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Stop implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $user = $update->findUser();
        if(!$chat || !$user) {
            return;
        }
        
        $game = $chat->game;
        if(!$game) {
            return;
        }

        if(!$game->hasPermission($user, $bot)) {
            $bot->sendMessage($chat->id, AppString::get('error.admin_only'), null, false, null, null, false, null, null, true);
            return;
        }
        
        $game->stop($bot);

        $bot->sendMessage($chat->id, AppString::get('game.stopped'));
    }

}