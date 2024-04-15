<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use App\Services\CallbackDataManager as CDM;
use Exception;

class SelectTeamAndRole implements Action {

    public function run($update, BotApi $bot) : Void {
        $updateId = $update->getId();
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();

        $user = User::find($update->getFrom()->getId());
        if(!$user || $user->status != 'actived') {
            $bot->sendAlertOrMessage($updateId, $chatId, 'error.user_not_registered');
            return;
        }

        $game = Game::where('chat_id', $chatId)->first();
        if(!$game) {
            $bot->deleteMessage($chatId, $message->getMessageId());
            $bot->sendAlertOrMessage($updateId, $chatId, 'game.no_game');
            return;
        }

        if($user->game_id && $user->game_id != $game->id) {
            $bot->sendAlertOrMessage($updateId, $chatId, 'error.already_playing');
            return;
        }

        $data = CDM::toArray($update->getData());
        $role = ($data[CDM::ROLE]==CDM::MASTER)?'master':'agent';
        if($role=='master') {
            $isThereAlreadyAMasterInSelectedTeam = $game->users->where('id', '!=', $user->id)
                ->where('team', $data[CDM::TEAM])
                ->where('role', 'master')
                ->count();
            if($isThereAlreadyAMasterInSelectedTeam) {
                $bot->sendAlertOrMessage($updateId, $chatId, 'game.master_occupied');
                return;
            }
        }

        $user->name = substr($update->getFrom()->getFirstName(), 0, 32);
        $user->username = $update->getFrom()->getUsername();
        $user->game_id = $game->id;
        $user->team = $data[CDM::TEAM];
        $user->role = ($data[CDM::ROLE]==CDM::MASTER)?'master':'agent';
        $user->save();

        Menu::send($game, $bot, Menu::EDIT, $message->getMessageId());
        try {
            $bot->answerCallbackQuery($updateId, AppString::get('game.updated'));
        } catch(Exception $e) {}
    }

}