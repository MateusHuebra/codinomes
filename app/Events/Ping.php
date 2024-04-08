<?php

namespace App\Events;

use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class Ping implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $bot->sendMessage($message->getChat()->getId(), 'pong');
        };
    }

}