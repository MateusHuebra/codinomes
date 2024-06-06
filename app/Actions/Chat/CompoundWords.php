<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class CompoundWords implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $chat = $update->findChat();
        $data = CDM::toArray($update->getData());

        if(isset($data[CDM::VALUE]) && $data[CDM::VALUE]==CDM::INFO) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'settings.compound_words_info');
            return;
        }

        if(!$user || $user->status != 'actived') {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.user_not_registered');
            return;
        }

        if(!$chat->hasPermission($user, $bot)) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.admin_only');
            return;
        }

        $chat->compound_words = !$chat->compound_words;
        $chat->save();

        $keyboard = Settings::getKeyboard($chat);
        $bot->editMessageReplyMarkup($chat->id, $update->getMessageId(), $keyboard);
    }

}