<?php

namespace App\UpdateHandlers;

use App\Actions\Chat\AdminOnly;
use App\Actions\Chat\ClickToSave;
use App\Actions\Chat\CompoundWords;
use App\Actions\Chat\Settings;
use App\Actions\Chat\Timer;
use App\Actions\Game\Color;
use App\Actions\Game\Menu;
use App\Actions\Chat\Pack;
use App\Actions\Game\Skip;
use App\Actions\Game\ConfirmSkip;
use App\Actions\Game\CancelSkip;
use App\Actions\DidYouKnow;
use App\Actions\Language\Set as SetLanguage;
use App\Actions\Notify;
use App\Actions\SetColor;
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
        
        } else if($data[CDM::EVENT] === CDM::CONFIRM_SKIP) {
            return new ConfirmSkip;
        
        } else if($data[CDM::EVENT] === CDM::CANCEL_SKIP) {
            return new CancelSkip;
        
        } else if($data[CDM::EVENT] === CDM::CHANGE_COLOR) {
            return new Color;
        
        } else if($data[CDM::EVENT] === CDM::CHANGE_DEFAULT_COLOR) {
            return new SetColor;

        } else if($data[CDM::EVENT] === CDM::SETTINGS) {
            return new Settings;

        } else if($data[CDM::EVENT] === CDM::TURN_NOTIFY_OFF) {
            return new Notify;

        } else if($data[CDM::EVENT] === CDM::CHANGE_ADMIN_ONLY) {
            return new AdminOnly;

        } else if($data[CDM::EVENT] === CDM::CHANGE_COMPOUND_WORDS) {
            return new CompoundWords;

        } else if($data[CDM::EVENT] === CDM::CHANGE_CLICK_TO_SAVE) {
            return new ClickToSave;

        } else if($data[CDM::EVENT] === CDM::CHANGE_TIMER) {
            return new Timer;
        
        } else if($data[CDM::EVENT] === CDM::CHANGE_PACK) {
            return new Pack;

        } else if($data[CDM::EVENT] === CDM::SET_LANGUAGE) {
            return new SetLanguage;
            
        } else if($data[CDM::EVENT] === CDM::MENU) {
            return new Menu;
            
        } else if($data[CDM::EVENT] === CDM::DID_YOU_KNOW) {
            return new DidYouKnow;
            
        } else {
            return null;
        }

    }

}