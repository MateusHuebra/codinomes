<?php

namespace App\UpdateHandlers;

use App\Actions\Game\ChosenGuess\Factory as ChosenGuessFactory;
use App\Actions\Game\ChosenHint;
use App\Services\CallbackDataManager as CDM;

class ChosenInlineResult implements UpdateHandler {

    public function getAction($update) {
        $data = CDM::toArray($update->getResultId());
        $game = $update->findUser()->currentGame();

        if($data[CDM::EVENT] === CDM::IGNORE) {
            return null;

        } else if($data[CDM::EVENT] === CDM::HINT) {
            return new ChosenHint;

        } else if($data[CDM::EVENT] === CDM::GUESS) {
            return ChosenGuessFactory::build($game->mode);

        }

    }

}