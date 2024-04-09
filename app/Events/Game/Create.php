<?php

namespace App\Events\Game;

use App\Events\Event;
use App\Models\Game;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class Create implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            if(Game::where('chat_id', $message->getChat()->getId())->exists()) {
                $bot->sendMessage($message->getChat()->getId(), AppString::get('game.already_exists'), null, false, $message->getMessageId(), null, false, null, null, true);
                return;
            }
            $game = new Game();
            $game->status = 'creating';
            $game->chat_id = $message->getChat()->getId();
            $game->save();

            Menu::send($game, $bot);
        };
    }

}