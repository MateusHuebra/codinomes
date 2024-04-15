<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\Game\Table;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenHint implements Action {

    public function run($update, BotApi $bot) : Void {
        $user = User::find($update->getFrom()->getId());
        $game = Game::find($user->game_id);
        
        if(!(($game->status=='master_a' && $user->team=='a' && $user->role=='master') || ($game->status=='master_b' && $user->team=='b' && $user->role=='master'))) {
            return;
        }

        $data = CDM::toArray($update->getResultId());
        $hint = $data[CDM::TEXT].' '.$data[CDM::NUMBER];
        $historyLine = Game::TEAM[$user->team]['emoji'].' '.$hint;
        $game->addToHistory($historyLine);
        
        $game->updateStatus('agent_'.$user->team);
        $game->attempts_left = $data[CDM::NUMBER];
        $game->save();

        
        Table::send($game, $bot, $hint, false);
    }

}