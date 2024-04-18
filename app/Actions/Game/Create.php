<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Create implements Action {

    public function run($update, BotApi $bot) : Void {
        $chatId = $update->getMessage()->getChat()->getId();
        $userId = $update->getMessage()->getFrom()->getId();
        $messageId = $update->getMessage()->getMessageId();
        if(!User::find($userId)) {
            $bot->sendMessage($chatId, AppString::get('error.user_not_registered'), null, false, $messageId, null, false, null, null, true);
            return;
        }
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
        $game->creator_id = $userId;
        $game->save();

        Menu::send($game, $bot);
    }

}