<?php

namespace App\Actions;

use App\Actions\Language\Get as GetLanguage;
use App\Models\Chat;
use App\Models\User;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Start implements Action {

    public function run($update, BotApi $bot) : Void {
        $message = $update->getMessage();
        if($message->getChat()->getType()==='private') {
            $this->checkIfUserOrChatExists($message->getFrom(), User::class, $bot, 'start.welcome', $message->getMessageId());
            
        } else if($message->getChat()->getType()==='supergroup') {
            $this->checkIfUserOrChatExists($message->getChat(), Chat::class, $bot, 'language.choose_chat', $message->getMessageId());
        }
    }

    private function checkIfUserOrChatExists($tgModel, string $modelClass, BotApi $bot, string $stringPath, int $messageId) {
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