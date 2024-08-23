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

        $values = [3, 5, 7, 10, 30, null];
        $key = array_search($chat->timer, $values);
        if($data[CDM::VALUE]==CDM::UP) {
            $key++;
        } else {
            $key--;
        }

        if($key < 0) {
            $key = 5;
        } else if ($key > 5) {
            $key = 0;
        }

        $chat->timer = $values[$key];
        $chat->save();

        $keyboard = Settings::getKeyboard($chat);
        $bot->editMessageReplyMarkup($chat->id, $update->getMessageId(), $keyboard);
    }

}