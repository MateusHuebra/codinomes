<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenHint implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->game;
        
        if(!(($game->status=='master_a' && $user->team=='a' && $user->role=='master') || ($game->status=='master_b' && $user->team=='b' && $user->role=='master'))) {
            return;
        }

        $data = CDM::toArray($update->getResultId());
        $hint = $data[CDM::TEXT].' '.$data[CDM::NUMBER];
        $color = ($user->team=='a') ? $game->color_a : $game->color_b;
        $historyLine = Game::COLORS[$color].' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        
        $game->updateStatus('agent_'.$user->team);
        if(in_array($data[CDM::NUMBER], ['∞', 0])) {
            $game->attempts_left = null;
        } else {
            $game->attempts_left = $data[CDM::NUMBER];
        }
        $game->save();

        $caption = new Caption($hint, null, 50);
        
        try {
            $bot->sendMessage($game->chat_id, $historyLine);
        } catch(Exception $e) {}
        Table::send($game, $bot, $caption);
    }

}