<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Pack as PackModel;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Pack implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $chat = $update->findChat();
        if(!$user) {
            return;
        }
        if(!$chat->hasPermission($user, $bot)) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.admin_only'), null, false, null, null, false, null, null, true);
            return;
        }

        $data = CDM::toArray($update->getData());
        $pack = PackModel::find($data[CDM::TEXT]);
        if(!$pack || !$chat) {
            return;
        }

        if($data[CDM::NUMBER]) {
            $chat->packs()->attach($pack->id);
        } else {
            $chat->packs()->detach($pack->id);
        }
        
        $settings = new Settings();
        $settings->prepareAndSend($update, $bot, $chat, $user);
    }

}