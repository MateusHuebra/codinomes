<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\GuessData;

class EightBall extends Classic implements Action {

    protected function getCaption($game, $guessData) {
        return new Caption($guessData->title, $guessData->title??null, 30, $game->mode == Game::EMOJI);
    }

    protected function handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft,$player, $chatLanguage, $opponentCardsLeft) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, true);
        $attemptType = 'ally';
        //next
        if($game->attempts_left===null || $game->attempts_left >= 0) {
            if($cardsLeft == 0) {
                $title = AppString::get('game.8ball', null, $chatLanguage);

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

            } else {
                $game->nextStatus($user->getEnemyTeam());

                $title = AppString::get('game.correct', null, $chatLanguage);
                $text = $game->getLastHint();
            }

            $winner = null;
        }

        return new GuessData($title, $attemptType, $text, $winner); 
    }

    protected function handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage) : GuessData {
        //won
        if($cardsLeft == 0) {
            $this->handleMessage($update, $user, $card, $game, $emoji, $bot, true);
            $attemptType = 'ally';
            $color = $game->getColor($player->team);
            $title = AppString::get('game.win', [
                'team' => AppString::get('color.'.$color)
            ], $chatLanguage);
            $text = AppString::get('game.win_color', null, $chatLanguage);
            $winner = $player->team;
        //black
        } else {
            $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
            $attemptType = 'black';
            $color = $game->getColor($user->getEnemyTeam());
            $title = AppString::get('game.win', [
                'team' => AppString::get('color.'.$color)
            ], $chatLanguage);
            $text = AppString::get('game.win_black', null, $chatLanguage);
            $winner = $user->getEnemyTeam();
            
            UserAchievement::checkBlackCard($game, $cardsLeft, $player, $bot);
        }

        return new GuessData($title, $attemptType, $text, $winner);
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

        } else {
            $game->nextStatus($user->getEnemyTeam());

            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();
        }

        $winner = null;

        return new GuessData($title, $attemptType, $text, $winner);
    }

}