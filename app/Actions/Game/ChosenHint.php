<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Services\Game\Table;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenHint implements Action {

    public function run($update, BotApi $bot) : Void {
        $data = CDM::toArray($update->getResultId());
        $game = Game::find($data[CDM::GAME_ID]);
        $game->updateStatus('agents_'.$data[CDM::TEAM]);
        $game->attempts_left = $data[CDM::NUMBER];
        $game->save();

        $hint = CDM::TEXT.' '.CDM::NUMBER;
        Table::send($game, $bot, $hint, false);
    }

}