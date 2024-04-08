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
            $data = CDM::toArray($update->getData());
            if($data[CDM::TYPE]===CDM::USER) {
                /*
                if(!$user = User::find($update->getFrom()->getId())) {
                    try {
                        $bot->answerCallbackQuery($update->getId(), AppString::get('error.user_not_registered'));
                    } catch(Exception $e) {
                        $bot->sendMessage($data[CDM::USER_ID], AppString::get('error.user_not_registered'));
                    }
                    return;
                }
                */
                $user = User::find($update->getFrom()->getId());
                $user->language = $data[CDM::LANGUAGE];
                $user->save();
                AppString::$language = $user->language;

                if(isset($data[CDM::FIRST_TIME]) && $data[CDM::FIRST_TIME]==CDM::TRUE) {
                    $bot->sendMessage($data[CDM::CHAT_ID], AppString::get('start.questions'));
                } else {
                    try {
                        $bot->answerCallbackQuery($update->getId(), AppString::get('language.changed'));
                    } catch(Exception $e) {
                        $bot->sendMessage($data[CDM::CHAT_ID], AppString::get('language.changed'));
                    }
                }
            }
        };
    }

}