<?php

namespace App\UpdateHandlers;

use App\Actions\Game\ChosenGuess;
use App\Actions\Game\ChosenHint;
use App\Services\CallbackDataManager as CDM;

class ChosenInlineResult implements UpdateHandler {

    public function getAction($update) {
        $data = CDM::toArray($update->getResultId());

        if($data[CDM::EVENT] === CDM::IGNORE) {
            return null;

        } else if($data[CDM::EVENT] === CDM::HINT) {
            return new ChosenHint;

        } else if($data[CDM::EVENT] === CDM::GUESS) {
            return new ChosenGuess;

        }

    }

}