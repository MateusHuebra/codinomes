<?php

namespace App\Actions\Pack;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class WebApp implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('private')) {
            return;
        }

        $keyboard = new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('settings.open_manager'),
                    'web_app' => ['url' => 'https://codinomesbot.surge.sh/#/packs/yours']
                ]
            ]
        ]);
        $bot->sendMessage($update->getChatId(), 'Packs', null, false, null, $keyboard);
    }

}