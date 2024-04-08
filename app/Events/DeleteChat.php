<?php

namespace App\Events;

use App\Models\Chat;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Types\Message;

class DeleteChat implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            $chat = Chat::find($message->getChat()->getId());
            $chat->delete();
        };
    }

}