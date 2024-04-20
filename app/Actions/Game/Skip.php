<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;

class Skip implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->game;

        try {
            $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
        } catch(Exception $e) {}

        if(!$user || !$game) {
            return;
        }

        if(($game->status=='agent_a' && $user->team=='a' && $user->role=='agent') || ($game->status=='agent_b' && $user->team=='b' && $user->role=='agent')) {
            $game->updateStatus('master_'.$user->getEnemyTeam());
            $game->attempts_left = null;

            $title = AppString::get('game.skipped').' '.$game->getLastHint();
            $text = AppString::get('game.history');
            $caption = new Caption($title, $text);
            Table::send($game, $bot, $caption);
        }
    }

}