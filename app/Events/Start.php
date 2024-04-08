<?php

namespace App\Events;

use App\Models\User;
use App\Services\CallbackDataManager as CDM;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class Start implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            $tgUser = $message->getFrom();
            $user = User::find($tgUser->getId());
            if(!$user && $message->getChat()->getType()==='private') {
                $user = User::createUserFromTGUser($tgUser);
                $keyboard = GetLanguages::getKeyboard(true);
                $bot->sendMessage($user->id, AppString::get('start.welcome'), null, false, null, $keyboard);
                return;
            }
            $bot->sendMessage($message->getChat()->getId(), AppString::get('start.questions'), null, false, $message->getMessageId(), null, false, null, null, true);
        };
    }

}