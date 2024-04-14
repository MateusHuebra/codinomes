<?php

namespace App\UpdateHandlers;

use App\Actions\Game\Skip;
use App\Actions\Language\Set as SetLanguage;
use App\Actions\Start;
use App\Actions\Ping;
use App\Actions\Game\Leave;
use App\Actions\Game\Start as StartGame;
use App\Actions\Game\SelectTeamAndRole;
use App\Services\CallbackDataManager as CDM;

class CallbackQuery implements UpdateHandler {

    public function getAction($update) {
        $data = CDM::toArray($update->getData());

        if($data[CDM::EVENT] === 'start') {
            return new Start;

        } else if($data[CDM::EVENT] === 'ping') {
            return new Ping;

        } else if($data[CDM::EVENT] === CDM::SELECT_TEAM_AND_ROLE) {
            return new SelectTeamAndRole;
        
        } else if($data[CDM::EVENT] === CDM::LEAVE_GAME) {
            return new Leave;
        
        } else if($data[CDM::EVENT] === CDM::START_GAME) {
            return new StartGame;
        
        } else if($data[CDM::EVENT] === CDM::SKIP) {
            return new Skip;
        
        } else if($data[CDM::EVENT] === CDM::SET_LANGUAGE) {
            return new SetLanguage;
        }

    }

}