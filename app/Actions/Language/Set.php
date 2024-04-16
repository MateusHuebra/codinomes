<?php

namespace App\Actions\Language;

use App\Actions\Action;
use App\Models\Chat;
use App\Models\User;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\Types\Message;
use Exception;
use TelegramBot\Api\Types\Update;

class Set implements Action {

    public function run($update, BotApi $bot) : Void {
        $message = $update->getMessage();
        try {
            $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
        } catch(Exception $e) {
            //
        }
        $data = CDM::toArray($update->getData());
        $user = User::find($update->getFrom()->getId());
        if($message->getChat()->getType()==='private') {
            $chatId = $user->id;
            $this->setUserOrChatLanguage($user, $data, $bot, $message);
            
        } else if($message->getChat()->getType()==='supergroup') {
            $chat = Chat::find($message->getChat()->getId());
            $chatId = $chat->id;
            if($chat->isAdmin($user, $bot) || $data[CDM::FIRST_TIME]) {
                $this->setUserOrChatLanguage($chat, $data, $bot, $message);
            } else {
                $bot->sendMessage($chatId, AppString::get('error.admin_only'));
            }

        }

        if($data[CDM::FIRST_TIME]) {
            $bot->sendMessage($chatId, AppString::get('start.questions'));
        }
    }

    private function setUserOrChatLanguage(Model $model, Array $data, BotApi $bot, Message $message) {
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