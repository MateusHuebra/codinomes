<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Actions\Language\Get as GetLanguage;
use App\Models\Chat;
use App\Models\User;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Start implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('private')) {
            $this->checkIfUserOrChatExists($update->getFrom(), User::class, $bot, 'start.welcome', $update->getMessageId());
            
        } else if($update->isChatType('supergroup')) {
            $this->checkIfUserOrChatExists($update->getChat(), Chat::class, $bot, 'language.choose_chat', $update->getMessageId());
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