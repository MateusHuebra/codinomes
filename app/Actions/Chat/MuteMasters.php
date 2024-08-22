<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class MuteMasters implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $chat = $update->findChat();
        $data = CDM::toArray($update->getData());

        if(isset($data[CDM::VALUE]) && $data[CDM::VALUE]==CDM::INFO) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'settings.mute_masters_info');
            return;
        }

        if(!$user || $user->status != 'actived') {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.user_not_registered');
            return;
        }

        if(!$chat->hasPermission($user, $bot)) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.admin_only');
            return;
        }

        $chatMember = $bot->getChatMember($chat->id, env('TG_BOT_ID'));
        if(!$chatMember->getCanRestrictMembers()) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.bot_needs_admin');
            return;
        }

        $chat->mute_masters = !$chat->mute_masters;
        $chat->save();

        $keyboard = Settings::getKeyboard($chat);
        $bot->editMessageReplyMarkup($chat->id, $update->getMessageId(), $keyboard);
    }

}