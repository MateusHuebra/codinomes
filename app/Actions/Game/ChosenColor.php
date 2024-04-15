<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\AppString;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenColor implements Action {

    public function run($update, BotApi $bot) : Void {
        $user = User::find($update->getFrom()->getId());
        $game = Game::find($user->game_id);
        
        if(!($game->status=='creating' && $user->role=='master')) {
            $bot->sendMessage($game->chat_id, AppString::get('error.master_only'));
            return;
        }

        $data = CDM::toArray($update->getResultId());
        $newColor = $data[CDM::TEXT];
        if($user->team=='a') {
            $yourColor = 'color_a';
            $enemyColor = 'color_b';
        } else {
            $yourColor = 'color_b';
            $enemyColor = 'color_a';
        }

        if($newColor == $game->$enemyColor) {
            $bot->sendMessage($game->chat_id, AppString::get('error.color_taken'));
            return;
        }
        
        $game->$yourColor = $newColor;
        $game->save();
        
        Menu::send($game, $bot);
    }

}