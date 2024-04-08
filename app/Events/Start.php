<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Message;

class Start implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            AppString::setLanguage($message);
            if($message->getChat()->getType()==='private') {
                $tgUser = $message->getFrom();
                $user = User::find($tgUser->getId());
                if(!$user) {
                    $user = User::createFromTGUser($tgUser);
                    $keyboard = GetLanguages::getKeyboard(true);
                    $bot->sendMessage($user->id, AppString::get('start.welcome'), null, false, null, $keyboard);
                    return;
                }
            } else if($message->getChat()->getType()==='supergroup') {
                $tgChat = $message->getChat();
                $chat = Chat::find($tgChat->getId());
                if(!$chat) {
                    $chat = Chat::createFromTGChat($tgChat);
                    $keyboard = GetLanguages::getKeyboard(true);
                    $bot->sendMessage($chat->id, AppString::get('language.choose_chat'), null, false, null, $keyboard);
                    return;
                }
            }
            $bot->sendMessage($message->getChat()->getId(), AppString::get('start.questions'), null, false, $message->getMessageId(), null, false, null, null, true);
        };
    }

}