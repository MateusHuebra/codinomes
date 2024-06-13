<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use TelegramBot\Api\BotApi;

class Table implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        if (!$chat) {
            return;
        }
        $game = $chat->currentGame();
        if ($game) {
            \App\Services\Game\Table::send($game, $bot, New Caption($game->getLastHint()));
        } else {
            $bot->sendMessage($chat->id, AppString::get('error.no_game'), null, false, $update->getMessageId(), null, false, null, null, true);
        }
        
    }

}