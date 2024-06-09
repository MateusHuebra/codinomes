<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class Ping implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $text = '';
        if($update->getChatId() == env('TG_MY_ID')) {
            $laravelTime = CONTROLLER_START - LARAVEL_START;
            $controllerTime = microtime(true) - CONTROLLER_START;
            $totalTime = $laravelTime + $controllerTime;
            $laravelTime = substr($laravelTime, 0, 5);
            $controllerTime = substr($controllerTime, 0, 5);
            $totalTime = substr($totalTime, 0, 5);
            $text = "\n {$laravelTime}s for laravel\n+{$controllerTime}s for processes\n= {$totalTime}";
        }
        
        $bot->sendMessage($update->getChatId(), 'pong'.$text, null, false, $update->getMessageId(), null, false, null, null, true);
    }

}