<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Chat;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use App\Actions\Language\Get as GetLanguage;

class Add implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($chat = $update->findChat()) {
            $chat->username = $update->getChatUsername();
            $chat->title = mb_substr($update->getChatTitle(), 0, 32, 'UTF-8');
            $chat->actived = true;
            $chat->save();
            return;
        }
        
        $chat = Chat::createFromTGModel($update->getChat());
        $keyboard = GetLanguage::getKeyboard(true);
        
        $bot->sendMessage($chat->id, AppString::get('language.choose_chat'), null, false, null, $keyboard);
    }

}