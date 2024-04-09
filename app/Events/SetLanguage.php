<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use App\Services\CallbackDataManager as CDM;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\Types\CallbackQuery;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\Types\Message;

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
                self::setUserOrChatLanguage($user, $data, $bot, $message);
                
            } else if($message->getChat()->getType()==='supergroup') {
                $chat = Chat::find($message->getChat()->getId());
                if($data[CDM::FIRST_TIME] || $chat->isTgUserAdmin($update->getFrom(), $bot)) {
                    self::setUserOrChatLanguage($chat, $data, $bot, $message);
                } else {
                    $bot->sendMessage($chat->id, AppString::get('error.admin_only'));
                }

            }

            if($data[CDM::FIRST_TIME]) {
                $bot->sendMessage($chat->id, AppString::get('start.questions'));
            }

        };
    }

    private static function setUserOrChatLanguage(Model $model, Array $data, BotApi $bot, Message $message) {
        $model->language = $data[CDM::LANGUAGE];
        $model->save();
        AppString::$language = $model->language;
        try {
            $bot->editMessageText($model->id, $message->getMessageId(), AppString::get('language.changed'));
        } catch(Exception $e) {
            $bot->sendMessage($model->id, AppString::get('language.changed'));
        }
    }

}