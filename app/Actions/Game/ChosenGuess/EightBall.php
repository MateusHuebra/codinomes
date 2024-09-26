<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\GuessData;

class EightBall extends Classic implements Action {

    protected function handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft,$player, $chatLanguage, $opponentCardsLeft) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, true);

        //next
        if($game->attempts_left===null || $game->attempts_left >= 0) {
            if($cardsLeft == 0) {
                $title = AppString::get('game.8ball', null, $chatLanguage);
                $text = null;

            } else {
                $title = AppString::get('game.correct', null, $chatLanguage);
                $text = $game->getLastHint();
            }
            $winner = null;

        //skip
        } else {
            if($opponentCardsLeft == 0) {
                $game->updateStatus('playing', $user->getEnemyTeam(), 'agent');
                $game->attempts_left = null;
                $game->setEightBallToHistory($player);

                $title = AppString::get('game.8ball', null, $chatLanguage);
                $text = null;

            } else {
                $game->nextStatus($user->getEnemyTeam());

                $title = AppString::get('game.correct', null, $chatLanguage);
                $text = $game->getLastHint();
            }

            $winner = null;
        }

        return new GuessData($title, 'ally', $text, $winner); 
    }

    protected function handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage) : GuessData {
        $isEightBallTime = $cardsLeft == 0;
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, $isEightBallTime);

        //won
        if($isEightBallTime) {
            $guessData = $this->getWinningGuessData($game, $player->team, $chatLanguage);
            $guessData->attemptType = 'ally';

            $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
            UserAchievement::add($agents, 'every_shot', $bot, $game->chat_id);

        //black
        } else {
            $guessData = $this->getWinningGuessData($game, $user->getEnemyTeam(), $chatLanguage, 'win_black');
            $guessData->attemptType = 'black';

            UserAchievement::checkBlackCard($game, $cardsLeft, $player, $bot);
        }

        return $guessData;
    }

    protected function handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $attemptType = $card->team == 'w' ? 'white' : 'opponent';

        //skip
        if($opponentCardsLeft == 0) {
            $game->updateStatus('playing', $user->getEnemyTeam(), 'agent');
            $game->attempts_left = null;
            $game->setEightBallToHistory($player);

            $title = AppString::get('game.8ball', null, $chatLanguage);
            $text = null;

        } else {
            $game->nextStatus($user->getEnemyTeam());

            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();
        }

        $winner = null;

        return new GuessData($title, $attemptType, $text, $winner);
    }

}