<?php

namespace App\Actions;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

interface Action {

    public function run($update, BotApi $bot) : Void;

  }