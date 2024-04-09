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
                self::checkIfUserOrChatExists($message->getFrom(), User::class, $bot, 'start.welcome');
                
            } else if($message->getChat()->getType()==='supergroup') {
                self::checkIfUserOrChatExists($message->getChat(), Chat::class, $bot, 'language.choose_chat');
            }
            $bot->sendMessage($message->getChat()->getId(), AppString::get('start.questions'), null, false, $message->getMessageId(), null, false, null, null, true);
        };
    }

    private static function checkIfUserOrChatExists($tgModel, string $modelClass, BotApi $bot, string $stringPath) {
        $model = $modelClass::find($tgModel->getId());
        if(!$model) {
            $model = $modelClass::createFromTGModel($tgModel);
            $keyboard = GetLanguages::getKeyboard(true);
            $bot->sendMessage($model->id, AppString::get($stringPath), null, false, null, $keyboard);
            return;
        }
    }

}