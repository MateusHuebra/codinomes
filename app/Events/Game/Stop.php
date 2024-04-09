<?php

namespace App\Events\Game;

use App\Events\Event;
use App\Models\Game;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class Stop implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $game = Game::where('chat_id', $message->getChat()->getId())->first();
            if(!$game) {
                return;
            }
            foreach($game->users as $user) {
                $user->game_id = null;
                $user->team = null;
                $user->role = null;
                $user->save();
            }
            $game->delete();

            $bot->sendMessage($message->getChat()->getId(), AppString::get('game.stopped'));
        };
    }

}