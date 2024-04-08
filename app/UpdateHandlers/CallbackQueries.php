<?php

namespace App\UpdateHandlers;

use App\Events\SetLanguage;
use App\Events\Start;
use App\Events\Ping;
use App\Services\CallbackDataManager as CDM;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\CallbackQuery;

class CallbackQueries implements UpdateHandler {

    static function addEvents(Client $client, BotApi $bot) : Client {
        $client->callbackQuery(function (CallbackQuery $update) use ($bot) {
            $data = CDM::toArray($update->getData());
            $message = $update->getMessage();
            AppString::setLanguage($message);

            if($data[CDM::EVENT] === 'start') {
                call_user_func(Start::getEvent($bot), $message);

            } else if($data[CDM::EVENT] === 'ping') {
                call_user_func(Ping::getEvent($bot), $message);

            } else if($data[CDM::EVENT] === CDM::SET_LANGUAGE) {
                call_user_func(SetLanguage::getEvent($bot), $update);
            }
        });

        return $client;
    }

}