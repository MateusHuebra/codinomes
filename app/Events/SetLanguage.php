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
            try {
                $bot->answerCallbackQuery($update->getId(), '!');
            } catch(Exception $e) {
                //
            }
            $data = CDM::toArray($update->getData());
            if($data[CDM::TYPE]===CDM::USER) {
                $user = User::find($data[CDM::USER_ID]);
                $user->language = $data[CDM::LANGUAGE];
                $user->save();
                TextString::$language = $user->language;

                if(isset($data[CDM::FIRST_TIME]) && $data[CDM::FIRST_TIME]==CDM::TRUE) {
                    $bot->sendMessage($data[CDM::USER_ID], AppString::get('start.questions'));
                } else {
                    $bot->sendMessage($data[CDM::USER_ID], AppString::get('language.changed'));
                }
            }
        };
    }

}