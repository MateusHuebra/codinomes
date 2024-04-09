<?php

namespace App\Events\Chat;

use App\Events\Event;
use App\Models\Chat;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;
use App\Events\Language\Get as GetLanguage;

class Add implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $tgChat = $message->getChat();
            $chat = Chat::createFromTGChat($tgChat);
            $keyboard = GetLanguage::getKeyboard(true);
            $bot->sendMessage($chat->id, AppString::get('language.choose_chat'), null, false, null, $keyboard);
        };
    }

}