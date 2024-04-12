<?php

namespace App\Actions;

use App\Services\Telegram\BotApi;
use TelegramBot\Api\Types\Update;

interface Action {

    public function run($update, BotApi $bot) : Void;

  }