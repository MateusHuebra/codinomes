<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Services\Telegram\BotApi;
use App\Services\AppString;

class Create implements Action {

    public function run($update, BotApi $bot) : Void {
        $chatId = $update->getMessage()->getChat()->getId();
        $messageId = $update->getMessage()->getMessageId();
        if(Game::where('chat_id', $chatId)->exists()) {
            $bot->sendMessage($chatId, AppString::get('game.already_exists'), null, false, $messageId, null, false, null, null, true);
            return;
        }
        if(!in_array($chatId, explode(',', env('TG_OFICIAL_GROUPS_IDS')))) {
            $bot->sendMessage($chatId, AppString::get('error.only_oficial_groups'), null, false, $messageId, null, false, null, null, true);
            return;
        }
        $game = new Game();
        $game->status = 'creating';
        $game->chat_id = $chatId;
        $game->save();

        Menu::send($game, $bot);
    }

}