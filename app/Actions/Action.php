<?php

namespace App\Actions;

use TelegramBot\Api\BotApi;
use App\Adapters\UpdateTypes\Update;

interface Action {

    public function run(Update $update, BotApi $bot) : Void;

}