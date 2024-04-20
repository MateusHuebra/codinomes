<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
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
        $color = ($user->team=='a') ? $game->color_a : $game->color_b;
        $historyLine = Game::COLORS[$color].' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        
        $game->updateStatus('agent_'.$user->team);
        $game->attempts_left = $data[CDM::NUMBER];
        $game->save();

        $caption = new Caption($hint, null, 50);
        
        try {
            $bot->sendMessage($game->chat_id, $historyLine);
        } catch(Exception $e) {}
        Table::send($game, $bot, $caption);
    }

}