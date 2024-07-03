<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\GuessData;

class Mystery extends Classic implements Action {

    protected function getCaption(GuessData $guessData, Game $game) {
        if($guessData->winner === null) {
            $hint = $game->getLastHint();
            $titleSize = strlen($hint) >= 16 ? 40 : 50;
            return new Caption($hint, null, $titleSize);

        } else {
            return parent::getCaption($guessData, $game);
        }
    }

    protected function handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $attemptType = $card->team == 'w' ? 'white' : 'opponent';
        
        //won
        if($attemptType == 'opponent' && $game->cards->where('team', $card->team)->where('revealed', false)->count() <= 0) {
            $guessData = $this->getWinningGuessData($game, $card->team, $chatLanguage);
            $guessData->attemptType = $attemptType;
            
            $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
            UserAchievement::add($agents, 'impostor', $bot, $game->chat_id);
        
        //skip
        } else {
            if($game->attempts_left < 0) {
                $game->nextStatus($user->getEnemyTeam());
            }

            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();

            $guessData = new GuessData($title, $attemptType, $text);
        }

        return $guessData;
    }

    protected function getEmojiToHandleMessage(string $emoji) {
        return '❔';
    }

    protected function getStringToHandleMessage(bool $isGuessCorrect) {
        return 'attempted';
    }

}