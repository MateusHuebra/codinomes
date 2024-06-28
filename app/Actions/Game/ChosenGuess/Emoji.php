<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\GuessData;

class Emoji extends Classic implements Action {

    protected function getCaption(GuessData $guessData, Game $game) {
        return new Caption($guessData->title, $guessData->title??null, 30, true);
    }

}