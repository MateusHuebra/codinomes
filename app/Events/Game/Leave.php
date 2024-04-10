<?php

namespace App\Events\Game;

use App\Events\Event;
use App\Models\Chat;
use App\Models\User;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\CallbackQuery;

class Leave implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (CallbackQuery $update) use ($bot) {
            $user = User::find($update->getFrom()->getId());
            if(!$user || !$user->game_id) {
                $bot->answerCallbackQuery($update->getId());
                return;
            }

            $chat = Chat::find($update->getMessage()->getChat()->getId());
            if($user->game_id != $chat->game->id) {
                $bot->sendAlertOrMessage($update->getId(), $chat->id, 'error.already_playing');
                return;
            }

            $user->leaveGame();
            $bot->answerCallbackQuery($update->getId(), AppString::get('you_left'));
        };
    }

}