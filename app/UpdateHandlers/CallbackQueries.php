<?php

namespace App\UpdateHandlers;

use App\Events\Start;
use App\Events\Ping;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\CallbackQuery;

class CallbackQueries implements UpdateHandler {

    static function addEvents(Client $client, BotApi $bot) : Client {
        $client->callbackQuery(function (CallbackQuery $update) use ($bot) {
            $data = json_decode($update->getData());
            $message = $update->getMessage();
            if($data->event === 'start') {
                call_user_func(Start::getEvent($bot), $message);
            } else if($data->event === 'ping') {
                call_user_func(Ping::getEvent($bot), $message);
            }
        });

        return $client;
    }

}