<?php

namespace App\Events;

use App\Services\Telegram\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Ping implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function ($message) use ($bot) {
            $keyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => 'Start',
                        'callback_data' => json_encode([
                            'event' => 'start'
                            ])
                    ]
                ]
            ]);
            $bot->sendMessage($message->getChat()->getId(), 'pong!', null, false, null, $keyboard);
        };
    }

}