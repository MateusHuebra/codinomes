<?php

namespace App\Events;

use App\Events\Language\Get as GetLanguage;
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
                self::checkIfUserOrChatExists($message->getFrom(), User::class, $bot, 'start.welcome', $message->getMessageId());
                
            } else if($message->getChat()->getType()==='supergroup') {
                self::checkIfUserOrChatExists($message->getChat(), Chat::class, $bot, 'language.choose_chat', $message->getMessageId());
            }
        };
    }

    private static function checkIfUserOrChatExists($tgModel, string $modelClass, BotApi $bot, string $stringPath, int $messageId) {
        $model = $modelClass::find($tgModel->getId());
        if(!$model) {
            $model = $modelClass::createFromTGModel($tgModel);
            $keyboard = GetLanguage::getKeyboard(true);
            $bot->sendMessage($model->id, AppString::get($stringPath), null, false, null, $keyboard);
            return;
        }
        $bot->sendMessage($model->id, AppString::get('start.questions'), null, false, $messageId, null, false, null, null, true);
    }

}