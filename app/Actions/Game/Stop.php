<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Stop implements Action {

    public function run($update, BotApi $bot) : Void {
        $chatId = $update->getMessage()->getChat()->getId();
        $userId = $update->getMessage()->getFrom()->getId();
        $game = Game::where('chat_id', $chatId)->first();
        $user = User::find($userId);
        if(!$game || !$user) {
            return;
        }
        if(!$game->hasPermission($user, $bot)) {
            $bot->sendMessage($chatId, AppString::get('error.admin_only'), null, false, null, null, false, null, null, true);
            return;
        }

        $game->stop();

        $bot->sendMessage($chatId, AppString::get('game.stopped'));
    }

}