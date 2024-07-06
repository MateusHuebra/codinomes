<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\GlobalSettings;
use TelegramBot\Api\BotApi;

class OfficialGroupOnly implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->getFromId() != env('TG_MY_ID')) {
            return;
        }

        $settings = GlobalSettings::first();
        $settings->official_groups_only = !$settings->official_groups_only;
        $settings->save();

        $on = !$settings->official_groups_only;

        $emoji = $on ? '🔥': '👨‍💻';
        $bot->setMessageReaction($update->getChatId(), $update->getMessageId(), $emoji);
        $bot->setMyName($on ? 'Codinomes' : 'Codinomes ⚠️ manutenção', 'pt');
        $bot->setMyName($on ? 'Codinomes' : 'Codinomes ⚠️ maintenance');
    }

}