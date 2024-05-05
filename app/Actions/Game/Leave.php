<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Leave implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user || !$user->game) {
            $bot->answerCallbackQuery($update->getCallbackQueryId());
            return;
        }

        $chat = $update->findChat();
        if($user->game_id != $chat->game->id) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.already_playing');
            return;
        }

        $user->leaveGame();
        Menu::send($chat->game, $bot);
        $bot->answerCallbackQuery($update->getCallbackQueryId(), AppString::get('game.you_left'));
    }

}