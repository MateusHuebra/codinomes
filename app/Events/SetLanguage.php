<?php

namespace App\Events;

use App\Models\User;
use App\Services\CallbackDataManager as CDM;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\Types\CallbackQuery;

class SetLanguage implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (CallbackQuery $update) use ($bot) {
            $message = $update->getMessage();
            try {
                $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
            } catch(Exception $e) {
                //
            }
            $data = CDM::toArray($update->getData());
            if($message->getChat()->getType()==='private') {
                $user = User::find($update->getFrom()->getId());
                $user->language = $data[CDM::LANGUAGE];
                $user->save();
                AppString::$language = $user->language;
                try {
                    $bot->editMessageText($user->id, $message->getMessageId(), AppString::get('language.changed'));
                } catch(Exception $e) {
                    $bot->sendMessage($user->id, AppString::get('language.changed'));
                }

                if($data[CDM::FIRST_TIME]) {
                    $bot->sendMessage($user->id, AppString::get('start.questions'));
                }
            }
        };
    }

}