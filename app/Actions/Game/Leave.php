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
        if(!$user || !$user->currentGame()) {
            $bot->answerCallbackQuery($update->getCallbackQueryId());
            return;
        }

        $chat = $update->findChat();
        if($user->currentGame()->id != $chat->currentGame()->id) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.already_playing');
            return;
        }

        $user->games()->detach($user->currentGame()->id);
        Menu::send($chat->currentGame(), $bot);
        $bot->answerCallbackQuery($update->getCallbackQueryId(), AppString::get('game.you_left'));
    }

}