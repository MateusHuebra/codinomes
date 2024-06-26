<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;

class Table implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if (!$user) {
            return;
        }
        if($update->isChatType('private')) {
            $game = $user->currentGame();
        } else {
            $chat = $update->findChat();
            if (!$chat) {
                return;
            }
            $game = $chat->currentGame();
        }

        if ($game) {
            if($game->status == 'playing') {
                if(
                    $game->role == 'agent'
                    ||
                    (
                        $user->currentGame()
                        &&
                        $user->currentGame()->player->role == 'master'
                        &&
                        $user->currentGame()->player->team == $game->team
                    )
                ) {
                    \App\Services\Game\Table::send($game, $bot, New Caption($game->getLastHint(), null, 40, $game->mode=='emoji'));
                    
                } else {
                    $bot->sendMessage($chat->id, AppString::get('error.only_agent_role'), null, false, $update->getMessageId(), null, false, null, null, true);
                }

            } else {
                Menu::send($game, $bot, true);
            }

        } else {
            $bot->sendMessage($chat->id, AppString::get('error.no_game'), null, false, $update->getMessageId(), null, false, null, null, true);
        }
        
    }

}