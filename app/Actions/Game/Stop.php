<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Stop implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user) {
            return;
        }
        if($update->isChatType('private')) {
            if(!$game = $user->currentGame()) {
                return;
            }
            if($game->mode == Game::COOP && $game->creator_id == $user->id) {
                $partner = $game->users()
                                    ->where('id', '!=', $user->id)
                                    ->first();
                $game->stop($bot);
                $bot->sendMessage($user->id, AppString::get('game.stopped'));
                if($partner) {
                    $bot->sendMessage($partner->id, AppString::get('game.stopped'));
                }
            }

        } else if($update->isChatType('supergroup')) {
            $chat = $update->findChat();
            if(!$chat) {
                return;
            }
            if(!$game = $chat->currentGame()) {
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

}