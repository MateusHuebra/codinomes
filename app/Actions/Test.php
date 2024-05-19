<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class Test implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $bot->sendMessage($update->getChatId(), 'start test '.$update->getUpdateId());
        sleep(5);
        $bot->sendMessage($update->getChatId(), 'end test '.$update->getUpdateId());
    }

}