<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Notify implements Action {
    
    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
            return;
        }
        $user = $update->findUser();
        $chat = $update->findChat();
        if(!$user || $user->status != 'actived') {
            $bot->sendMessage($chat->id, AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $toggled = $chat->notifiableUsers()->toggle([$user->id]);
        if(count($toggled['attached']) == 1) {
            $bot->setMessageReaction($chat->id, $update->getMessageId(), '✍️');
        } else {
            $bot->setMessageReaction($chat->id, $update->getMessageId(), '🙊');
        }
    }

}