<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Timer implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $chat = $update->findChat();
        $data = CDM::toArray($update->getData());

        if(isset($data[CDM::VALUE]) && $data[CDM::VALUE]==CDM::INFO) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'settings.timer_info');
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

        switch ($chat->timer) {
            case null:
                $chat->timer = 3;
                break;

            case 3:
                $chat->timer = 5;
                break;

            case 5:
                $chat->timer = 7;
                break;

            case 7:
                $chat->timer = 10;
                break;

            case 10:
                $chat->timer = 30;
                break;

            case 30:
                $chat->timer = null;
                break;
        }

        $chat->save();

        $keyboard = Settings::getKeyboard($chat);
        $bot->editMessageReplyMarkup($chat->id, $update->getMessageId(), $keyboard);
    }

}