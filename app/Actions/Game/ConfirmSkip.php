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
        if(!$update->isChatType('supergroup')) {
            return;
        }
        if(!$chat = $update->findChat()) {
            return;
        }
        if(!$user = $update->findUser()) {
            return;
        }
        if(!$game = $chat->currentGame()) {
            return;
        }
        $chatLanguage = $chat->language;

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
            if(!($game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team && $game->id === $user->currentGame()->id)) {
                if(!$game->chat->isAdmin($user, $bot)) {
                    return;
                }
                $adm = true;
            } else {
                $adm = false;
            }
            
        } else {
            if(!$game->chat->isAdmin($user, $bot)) {
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
        
        try {
            $bot->sendMessage($game->chat_id, $text, 'MarkdownV2');
        } catch(Exception $e) {}

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

        $caption = new Caption($title, $game->getLastHint(), 30, $game->mode==Game::EMOJI);
        Table::send($game, $bot, $caption, null);
    }

}