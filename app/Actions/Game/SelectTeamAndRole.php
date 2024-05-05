<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use App\Services\CallbackDataManager as CDM;
use Exception;

class SelectTeamAndRole implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $user = $update->findUser();
        if(!$user || $user->status != 'actived') {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.user_not_registered');
            return;
        }

        $game = $chat->game;
        if(!$game || $game->status != 'creating') {
            $bot->deleteMessage($chat->id, $update->getMessageId());
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'game.no_game');
            return;
        }

        if($user->game_id && $user->game_id != $game->id) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.already_playing');
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
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'game.master_occupied');
                return;
            }
        }

        $user->name = substr($update->getFrom()->getFirstName(), 0, 32);
        $user->username = $update->getFrom()->getUsername();
        $user->game_id = $game->id;
        $user->team = $data[CDM::TEAM];
        $user->role = ($data[CDM::ROLE]==CDM::MASTER)?'master':'agent';
        $user->save();

        Menu::send($game, $bot);
        try {
            $bot->answerCallbackQuery($update->getCallbackQueryId(), AppString::get('game.updated'));
        } catch(Exception $e) {}

        $notifiedMessageId = $chat->notifiableUsers()->where('user_id', $user->id)->first()->pivot->message_id??null;
        if($notifiedMessageId) {
            try {
                $bot->deleteMessage($user->id, $notifiedMessageId);
            } catch(Exception $e) {}
            $attachmentsToUpdate[$user->id] = ['message_id' => null];
            $chat->notifiableUsers()->syncWithoutDetaching($attachmentsToUpdate);
        }
    }

}