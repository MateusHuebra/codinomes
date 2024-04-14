<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Models\Chat;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;
use App\Actions\Language\Get as GetLanguage;

class Add implements Action {

    public function run($update, BotApi $bot) : Void {
        $tgChat = $update->getMessage()->getChat();
        $chat = Chat::createFromTGChat($tgChat);
        $keyboard = GetLanguage::getKeyboard(true);
        $bot->sendMessage($chat->id, AppString::get('language.choose_chat'), null, false, null, $keyboard);
    }

}