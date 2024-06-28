<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use TelegramBot\Api\BotApi;

class PrivateCoopMessage implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$user = $update->findUser()) {
            return;
        }
        if(!$game = $user->currentGame()) {
            return;
        }
        if($game->mode != Game::COOP) {
            return;
        }
        if($game->users()->count() < 2) {
            return;
        }

        $recipient = $game->users()
                            ->where('id', '!=', $user->id)
                            ->first();
        
        if($update->getMessageText() || $update->getMessage()->getSticker() || $update->getMessage()->getVoice()) {
            $bot->forwardMessage($recipient->id, $update->getChatId(), $update->getMessageId());
        }
    }

}