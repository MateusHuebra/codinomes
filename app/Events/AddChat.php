<?php

namespace App\Events;

use App\Models\Chat;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class AddChat implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $tgChat = $message->getChat();
            $chat = Chat::createUserFromTGChat($tgChat);
            $keyboard = GetLanguages::getKeyboard(true);
            $bot->sendMessage($chat->id, AppString::get('language.choose_chat'), null, false, null, $keyboard);
        };
    }

}