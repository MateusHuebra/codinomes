<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class History implements Action {

    public function run($update, BotApi $bot) : Void {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();

        $game = null;
        if($message->getChat()->getType()==='private') {
            $user = User::find($chatId);
            if($user->game_id) {
                $game = Game::find($user->game_id);
            }

        } else {
            $game = Game::where('chat_id', $chatId)->first();
        }
        
        if($game) {
            $text = AppString::parseMarkdownV2($game->history)??AppString::get('error.no_history');
        } else {
            $text = AppString::get('error.no_game');
        }
        
        $bot->sendMessage($chatId, $text, null, false, $message->getMessageId(), null, false, null, null, true);
    }

}