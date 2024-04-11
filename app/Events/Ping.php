<?php

namespace App\Events;

use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class Ping implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $text = '';
            if($message->getChat()->getId() == env('TG_MYID')) {
                $laravelTime = CONTROLLER_START - LARAVEL_START;
                $controllerTime = microtime(true) - CONTROLLER_START;
                $totalTime = $laravelTime + $controllerTime;
                $laravelTime = substr($laravelTime, 0, 5);
                $controllerTime = substr($controllerTime, 0, 5);
                $totalTime = substr($totalTime, 0, 5);
                $text = "\n {$laravelTime}s for laravel\n+{$controllerTime}s for processes\n= {$totalTime}";
            }
            
            $bot->sendMessage($message->getChat()->getId(), 'pong'.$text);
        };
    }

}