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
            if($user = $this->checkIfUserOrChatExists($update->getFrom(), User::class, $bot, 'start.welcome')) {
                $bot->sendMessage($user->id, AppString::get('start.questions'), null, false, $update->getMessageId(), null, false, null, null, true);
            }
            
        } else if($update->isChatType('supergroup')) {
            if($chat = $this->checkIfUserOrChatExists($update->getChat(), Chat::class, $bot, 'language.choose_chat')) {
                if($chat->game && $user = $update->findUser()) {
                    $chat->game->start($bot, $user, 0);
                } else {
                    $bot->sendMessage($chat->id, AppString::get('start.questions'), null, false, $update->getMessageId(), null, false, null, null, true);
                }
            }
        }
    }

    private function checkIfUserOrChatExists($tgModel, string $modelClass, BotApi $bot, string $stringPath) {
        $model = $modelClass::find($tgModel->getId());
        if(!$model) {
            $model = $modelClass::createFromTGModel($tgModel);
            $keyboard = GetLanguage::getKeyboard(true);
            $bot->sendMessage($model->id, AppString::get($stringPath), null, false, null, $keyboard);
            return false;
        }
        return $model;
        
    }

}