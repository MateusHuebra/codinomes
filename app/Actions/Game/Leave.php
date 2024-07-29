<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Leave implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user || !$user->currentGame()) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $update->getChatId(), 'error.no_game');
            return;
        }
        $game = $user->currentGame();

        $chat = $update->findChat();
        if(!$update->isChatType('private') && $game->id != $chat->currentGame()->id) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.already_playing');
            return;
        }
        
        if(!in_array($game->status, ['creating', 'lobby'])) {
            return;
        }

        if($game->mode == Game::COOP) {
            if($game->creator_id == $user->id) {
                $partner = $game->users()
                                 ->where('id', '!=', $user->id)
                                 ->first();
                $game->stop($bot);
                $bot->sendMessage($user->id, AppString::get('game.stopped'));
                if($partner) {
                    $bot->sendMessage($partner->id, AppString::get('game.stopped'));
                    $bot->sendMessage($user->id, AppString::get('game.dm_stop', null, $user->language));
                    $bot->sendMessage($partner->id, AppString::get('game.dm_stop', null, $partner->language));
                }
                return;
            }
        }
        
        $user->games()->detach($game->id);
        Menu::send($game, $bot);
        $bot->sendAlertOrMessage($update->getCallbackQueryId(), $update->getChatId(), 'game.you_left');
    }

}