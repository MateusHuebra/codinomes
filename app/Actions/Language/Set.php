<?php

namespace App\Actions\Language;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\User;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\Types\Message;
use Exception;

class Set implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        try {
            $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
        } catch(Exception $e) {
            //
        }
        $data = CDM::toArray($update->getData());
        $user = User::find($update->getFrom()->getId());
        if($update->isChatType('private')) {
            $chatId = $user->id;
            $this->setUserOrChatLanguage($user, $data, $bot, $update->getMessage());
            
        } else if($update->isChatType('supergroup')) {
            $chat = $update->findChat();
            $chatId = $chat->id;
            if($chat->isAdmin($user, $bot) || $data[CDM::FIRST_TIME]) {
                $this->setUserOrChatLanguage($chat, $data, $bot, $update->getMessage());
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