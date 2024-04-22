<?php

namespace App\Actions\Pack;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class WebApp implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $keyboard = new InlineKeyboardMarkup([
            [
                [
                    'text' => 'Abrir app',
                    'web_app' => ['url' => 'https://codinomesbot.surge.sh/#/packs/yours']
                ]
            ]
        ]);
        $bot->sendMessage($update->getChatId(), 'test', null, false, null, $keyboard);
    }

}