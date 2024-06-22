<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class LookingForAGame implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $bot->sendMessage($update->getChatId(), AppString::get('game.try_official_group'), null, false, $update->getMessageId(), null, false, null, null, true);
    }

}