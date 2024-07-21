<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;

class ConfirmSkip implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$user = $update->findUser()) {
            return;
        }
        if($update->isChatType('supergroup')) {
            if(!$chat = $update->findChat()) {
                return;
            }
            if(!$game = $chat->currentGame()) {
                return;
            }
            $chatLanguage = $chat->language;

        } else if($update->isChatType('private')) {
            if(!$game = $user->currentGame()) {
                return;
            }
            $chatLanguage = $user->language;

        } else {
            return;
        }

        if($update->isType(Update::CALLBACK_QUERY)) {
            try {
                $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
            } catch(Exception $e) {}
        } else if($update->isType(Update::MESSAGE)) {
            $bot->tryToDeleteMessage($update->getChatId(), $update->getMessageId());
        }

        if(!$game) {
            return;
        }
        
        if($user->currentGame()) {
            $player = $user->currentGame()->player;
            if(!(
                ($game->mode != Game::COOP && $game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team && $game->id === $user->currentGame()->id)
                || ($game->mode == Game::COOP && $game->role == $player->role)
            )) {
                if($game->mode == Game::COOP || !$game->chat->isAdmin($user, $bot)) {
                    return;
                }
                $adm = true;
            } else {
                $adm = false;
            }
            
        } else {
            if($game->mode == Game::COOP || !$game->chat->isAdmin($user, $bot)) {
                return;
            }
            $adm = true;
        }

        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], $chatLanguage, true);
        $skipped = strtolower(AppString::getParsed('game.skipped', null, $chatLanguage));
        $text = $mention.' '.$skipped;
        if($adm) {
            $text = '⚠️ Admin '.$text;
        }

        if($game->mode == Game::COOP) {
            if($game->attempts_left <= 0) {
                $bot->sendMessage($update->getChatId(), AppString::get('error.cannot_skip_sudden_death'));
                return;

            } else if($game->attempts_left == 1) {
                $title = AppString::get('game.sudden_death', null, $chatLanguage);
                $text = AppString::get('game.sudden_death_info', null, $chatLanguage);
                
            } else {
                $title = AppString::get('game.skipped', null, $chatLanguage);
            }

            try {
                $bot->sendMessage($game->creator->id, $text, 'MarkdownV2');
                $bot->sendMessage($game->getPartner()->id, $text, 'MarkdownV2');
            } catch(Exception $e) {}
    
            $game->attempts_left--;
            $game->role = null;
            $game->save();

        } else {
            $bot->sendMessage($game->chat_id, $text, 'MarkdownV2');
            $currentPlayer = $game->users()->fromTeamRole($game->team, 'agent')->first();
    
            if($game->mode == Game::EIGHTBALL && $game->cards->where('team', $currentPlayer->getEnemyTeam())->where('revealed', false)->count() == 0) {
                $game->updateStatus('playing', $currentPlayer->getEnemyTeam(), 'agent');
                $game->attempts_left = null;
                $game->setEightBallToHistory($player);
    
                $title = AppString::get('game.8ball', null, $chatLanguage);
    
            } else {
                $game->nextStatus($currentPlayer->getNextTeam());
    
                $title = AppString::get('game.skipped', null, $chatLanguage);
            }
        }

        $caption = new Caption($title, $text??$game->getLastHint(), 30, $game->mode==Game::EMOJI);
        Table::send($game, $bot, $caption, null);
    }

}