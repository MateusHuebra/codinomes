<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

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
        
        try {
            $bot->deleteMessage($chat->id, $game->message_id);
        } catch(Exception $e) {}
        $game->stop($bot);

        $bot->sendMessage($chat->id, AppString::get('game.stopped'));
    }

}