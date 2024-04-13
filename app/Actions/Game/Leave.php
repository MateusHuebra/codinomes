<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Chat;
use App\Models\User;
use App\Services\Telegram\BotApi;
use App\Services\AppString;

class Leave implements Action {

    public function run($update, BotApi $bot) : Void {
        $updateId = $update->getId();
        $message = $update->getMessage();
        $user = User::find($update->getFrom()->getId());
        if(!$user || !$user->game_id) {
            $bot->answerCallbackQuery($updateId);
            return;
        }

        $chat = Chat::find($message->getChat()->getId());
        if($user->game_id != $chat->game->id) {
            $bot->sendAlertOrMessage($updateId, $chat->id, 'error.already_playing');
            return;
        }

        $user->leaveGame();
        Menu::send($chat->game, $bot, Menu::EDIT, $message->getMessageId());
        $bot->answerCallbackQuery($updateId, AppString::get('game.you_left'));
    }

}