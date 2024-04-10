<?php

namespace App\Events\Game;

use App\Events\Event;
use App\Models\Game;
use App\Models\User;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use App\Services\CallbackDataManager as CDM;
use Exception;
use TelegramBot\Api\Types\CallbackQuery;

class SelectTeamAndRole implements Event {

    static function getEvent(BotApi $bot) : callable {
        return function (CallbackQuery $update) use ($bot) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();

            $user = User::find($update->getFrom()->getId());
            if(!$user || $user->status != 'actived') {
                $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.user_not_registered');
                return;
            }

            $game = Game::where('chat_id', $message->getChat()->getId())->first();
            if(!$game) {
                $bot->sendAlertOrMessage($update->getId(), $chatId, 'game.no_game');
                return;
            }

            if($user->game_id && $user->game_id != $game->id) {
                $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.already_playing');
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
                    $bot->sendAlertOrMessage($update->getId(), $chatId, 'game.master_occupied');
                    return;
                }
            }

            $user->game_id = $game->id;
            $user->team = $data[CDM::TEAM];
            $user->role = ($data[CDM::ROLE]==CDM::MASTER)?'master':'agent';
            $user->save();

            Menu::send($game, $bot, Menu::EDIT, $message->getMessageId());
            try {
                $bot->answerCallbackQuery($update->getId(), AppString::get('game.updated'));
            } catch(Exception $e) {}
        };
    }

}