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
        if($update->isChatType('supergroup')) {
            $chatId = $update->getChatId();
            $text = AppString::get('settings.stolen');

        } else if ($update->isChatType('private')) {
            $chatId = null;
            $text = AppString::get('settings.stop_steal');

        } else {
            return;
        }

        $user = $update->findUser();
        $user->coop_packs_chat_id = $chatId;
        $user->save();

        $bot->sendMessage($update->getChatId(), $text, null, false, $update->getMessageId(), null, false, null, null, true);
    }

}