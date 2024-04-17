<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Chat;
use App\Models\Game;
use App\Models\User;
use App\Services\AppString;
use App\Services\Game\Menu;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Color implements Action {

    public function run($update, BotApi $bot) : Void {
        $updateId = $update->getId();
        $messageId = $update->getMessage()->getMessageId();

        $user = User::find($update->getFrom()->getId());
        if(!$user->game_id) {
            return;
        }

        $game = Game::find($user->game_id);
        if(!($game->status=='creating' && $user->role=='master')) {
            $bot->sendAlertOrMessage($updateId, $game->chat_id, 'error.master_only');
            return;
        }

        $data = CDM::toArray($update->getData());
        $newColor = $data[CDM::TEXT];
        if($user->team=='a') {
            $yourColor = 'color_a';
            $enemyColor = 'color_b';
        } else {
            $yourColor = 'color_b';
            $enemyColor = 'color_a';
        }

        if($newColor == $game->$enemyColor) {
            $bot->sendAlertOrMessage($updateId, $game->chat_id, 'error.color_taken');
            return;
        }
        
        $game->$yourColor = $newColor;
        $game->menu = null;
        $game->save();
        
        Menu::send($game, $bot, MENU::EDIT, $messageId);
    }

}