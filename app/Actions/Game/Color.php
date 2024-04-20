<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Color implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user->game) {
            $bot->answerCallbackQuery($update->getCallbackQueryId());
            return;
        }

        $game = $user->game;
        if(!($game->status=='creating' && $user->role=='master')) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $game->chat_id, 'error.master_only');
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
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $game->chat_id, 'error.color_taken');
            return;
        }
        
        $game->$yourColor = $newColor;
        $game->menu = null;
        $game->save();
        
        Menu::send($game, $bot, $update->getMessageId());
    }

}