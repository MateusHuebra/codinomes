<?php

namespace App\Actions;

use App\Services\Telegram\BotApi;

class Ping implements Action {

    public function run($update, BotApi $bot) : Void {
        $message = $update->getMessage();
        $text = '';
        if($message->getChat()->getId() == env('TG_MY_ID')) {
            $laravelTime = CONTROLLER_START - LARAVEL_START;
            $controllerTime = microtime(true) - CONTROLLER_START;
            $totalTime = $laravelTime + $controllerTime;
            $laravelTime = substr($laravelTime, 0, 5);
            $controllerTime = substr($controllerTime, 0, 5);
            $totalTime = substr($totalTime, 0, 5);
            $text = "\n {$laravelTime}s for laravel\n+{$controllerTime}s for processes\n= {$totalTime}";
        }
        
        $bot->sendMessage($message->getChat()->getId(), 'pong'.$text);
    }

}