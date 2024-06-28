<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\GuessData;

class Mystery extends Classic implements Action {

    protected function getCaption($game, $guessData) {
        if($guessData->winner === null) {
            $hint = $game->getLastHint();
            $titleSize = strlen($hint) >= 16 ? 40 : 50;
            return new Caption($hint, null, $titleSize);

        } else {
            return parent::getCaption($game, $guessData);
        }
    }

    protected function handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $attemptType = $card->team == 'w' ? 'white' : 'opponent';
        //won
        if($game->cards->where('team', $card->team)->where('revealed', false)->count() <= 0) {
            $color = $game->getColor($card->team);
            $title = AppString::get('game.win', [
                'team' => AppString::get('color.'.$color)
            ], $chatLanguage);
            $text = AppString::get('game.win_color', null, $chatLanguage);
            $winner = $card->team;
            
            $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
            UserAchievement::add($agents, 'impostor', $bot, $game->chat_id);
        
        //skip
        } else {
            if($game->attempts_left < 0) {
                $game->nextStatus($user->getEnemyTeam());
            }

            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();

            $winner = null;
        }

        return new GuessData($title, $attemptType, $text, $winner);
    }

    protected function getEmojiToHandleMessage(string $emoji) {
        return '❔';
    }

    protected function getStringToHandleMessage(bool $isGuessCorrect) {
        return 'attempted';
    }

}