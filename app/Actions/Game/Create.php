<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GlobalSettings;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Create implements Action {

    private $mode;

    public function __construct(string $mode = 'classic') {
        $this->mode = $mode;
    }

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
            return;
        }
        $chat = $update->findChat();
        $chat->username = $update->getChatUsername();
        $chat->title = substr($update->getChatTitle(), 0, 32);
        $chat->save();

        $user = $update->findUser();
        if(!$user || !$chat) {
            $bot->sendMessage($chat->id, AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        if($chat->currentGame()) {
            $bot->sendMessage($chat->id, AppString::get('game.already_exists'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        if(GlobalSettings::first()->official_groups_only && !in_array($chat->id, explode(',', env('TG_OFICIAL_GROUPS_IDS')))) {
            $bot->sendMessage($chat->id, AppString::get('error.only_oficial_groups'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        
        $message = $bot->sendMessage($chat->id, AppString::get('game.creating'));

        $game = new Game();
        $game->status = 'creating';
        $game->mode = $this->mode;
        $game->chat_id = $chat->id;
        $game->creator_id = $user->id;
        $game->lobby_message_id = $message->getMessageId();
        $game->save();

        $chat->notifiableUsers->notify($game, $bot);
        Menu::send($game, $bot);
        $bot->tryToPinChatMessage($game->chat_id, $message->getMessageId());

        if($game->chat->packs()->count() == 0) {
            $bot->sendMessage($chat->id, AppString::get('error.no_packs'));
        }
    }

}