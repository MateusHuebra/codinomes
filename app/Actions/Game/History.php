<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class History implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('private')) {
            $game = $update->findUser()->currentGame();
            $ghost = $game->mode == 'ghost' && $game->player->role == 'agent';
        } else {
            $game = $update->findChat()->currentGame();
            $ghost = $game->mode == 'ghost';
        }
        
        if($game) {
            $text = $game->getHistory($ghost)??AppString::get('error.no_history');
        } else {
            $text = AppString::get('error.no_game');
        }
        
        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

}