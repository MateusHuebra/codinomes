<?php

namespace App\Actions\Language;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Get implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $keyboard = self::getKeyboard();
        if($update->isChatType('private')) {
            $stringPath = 'language.choose';
        } else if($update->isChatType('supergroup')) {
            $stringPath = 'language.choose_chat';
        } else {
            return;
        }
        $bot->sendMessage($update->getChatId(), AppString::get($stringPath), null, false, null, $keyboard);
    }

    public static function getKeyboard(bool $firstTime = false) : InlineKeyboardMarkup {
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