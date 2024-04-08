<?php

namespace App\Events;

use App\Models\User;
use App\Services\CallbackDataManager as CDM;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class GetLanguages implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $keyboard = GetLanguages::getKeyboard();
            if($message->getChat()->getType()==='private') {
                $bot->sendMessage($message->getChat()->getId(), AppString::get('language.choose'), null, false, null, $keyboard);
            } else if($message->getChat()->getType()==='supergroup') {
                $bot->sendMessage($message->getChat()->getId(), AppString::get('language.choose_chat'), null, false, null, $keyboard);
            }
        };
    }

    static function getKeyboard(bool $firstTime = false) {
        $keyboard = [];
        foreach (AppString::getAllLanguages() as $language) {
            $keyboard[] = [[
                    'text' => AppString::get('language.self', null, $language),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SET_LANGUAGE,
                        CDM::LANGUAGE => $language,
                        CDM::FIRST_TIME => $firstTime
                    ])
                ]];
        }
        return new InlineKeyboardMarkup($keyboard);
    }

}