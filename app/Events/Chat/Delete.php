<?php

namespace App\Events\Chat;

use App\Events\Event;
use App\Models\Chat;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Types\Message;

class Delete implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            $chat = Chat::find($message->getChat()->getId());
            $chat->delete();
        };
    }

}