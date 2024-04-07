<?php

namespace App\UpdateHandlers;

use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;

interface UpdateHandler {

    static function addEvents(Client $client, BotApi $bot) : Client;

  }