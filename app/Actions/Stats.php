<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Stats implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $data = print_r($user->stats->toArray(), true);
        $bot->sendMessage($update->getChatId(), '<blockquote expandable>'.$data.'</blockquote>', 'html');
        $data = print_r($user->colorStats->toArray(), true);
        $bot->sendMessage($update->getChatId(), '<blockquote expandable>'.$data.'</blockquote>', 'html');
    }

}