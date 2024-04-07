<?php

namespace App\Events;

use App\Services\Telegram\BotApi;

class Start implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function ($message) use ($bot) {
            $bot->sendMessage($message->getChat()->getId(), 'started');
        };
    }

}