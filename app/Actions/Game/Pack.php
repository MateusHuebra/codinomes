<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Models\Pack as PackModel;
use App\Services\AppString;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Pack implements Action {

    public function run($update, BotApi $bot) : Void {
        $messageId = $update->getMessage()->getMessageId();
        $chatId = $update->getMessage()->getChat()->getId();
        $userId = $update->getFrom()->getId();

        $user = User::find($userId);
        $game = Game::where('chat_id', $chatId)->first();
        if(!$game || !$user) {
            return;
        }
        if(!$game->hasPermission($user, $bot)) {
            $bot->sendMessage($chatId, AppString::get('error.admin_only'), null, false, null, null, false, null, null, true);
            return;
        }

        $data = CDM::toArray($update->getData());
        $pack = PackModel::find($data[CDM::TEXT]);
        $chat = $game->chat;
        if(!$pack || !$chat) {
            return;
        }

        if($data[CDM::NUMBER]) {
            $chat->packs()->attach($pack->id);
        } else {
            $chat->packs()->detach($pack->id);
        }
        
        Menu::send($game, $bot, $messageId);
    }

}