<?php

namespace App\UpdateHandlers;

use App\Events\GetLanguages;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use App\Events\Ping;
use App\Events\Start;

class Commands implements UpdateHandler {

    static function addEvents(Client $client, BotApi $bot) : Client {
        $client->command('start', Start::getEvent($bot));
        $client->command('ping', Ping::getEvent($bot));
        $client->command('language', GetLanguages::getEvent($bot));

        return $client;
    }

}