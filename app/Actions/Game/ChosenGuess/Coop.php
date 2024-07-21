<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\GuessData;
use Exception;
use TelegramBot\Api\BotApi;

class Coop extends Classic implements Action {

    protected function handle(Update $update, Game $game, User $user, $player, $card, $emoji, BotApi $bot, $chatLanguage) {
        if($player->role == 'master') {
            $card->coop_revealed = true;
            $cardTeam = $card->coop_team;
        } else {
            $card->revealed = true;
            $cardTeam = $card->team;
        }
        $card->save();

        $cardsLeft = 15 - $game->cards()
                          ->where(function ($query) {
                            $query->where('team', 'a')
                                  ->where('revealed', true);
                          })->orWhere(function ($query) {
                            $query->where('coop_team', 'a')
                                  ->where('coop_revealed', true);
                          })->where('game_id', $game->id)
                          ->count();
                          
        if($cardTeam == $player->team) {
            return $this->handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft, $player, $chatLanguage, null);

        } else if($cardTeam == 'x') {
            return $this->handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage);

        } else {
            return $this->handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, null, $player);
        }
    }

    protected function handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft,$player, $chatLanguage, $opponentCardsLeft) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, true);
        $attemptType = 'ally';
        
        //won
        if($cardsLeft <= 0) {
            $guessData = $this->getWinningGuessData($game, $player->team, $chatLanguage);
            $guessData->attemptType = $attemptType;

            if($game->getColor('a') == 'pink') {
                UserAchievement::add($game->users, 'lovebirds', $bot);
            }
            if($game->attempts_left == 0) {
                UserAchievement::add($game->users, 'thin_ice', $bot);
            }
            
        //next
        } else {
            $title = AppString::get('game.correct', null, $chatLanguage);
            $text = $game->getLastHint();

            $guessData = new GuessData($title, $attemptType, $text);
        }

        return $guessData; 
    }

    protected function handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $guessData = $this->getWinningGuessData($game, 'x', $chatLanguage, 'win_black_coop');
        $guessData->attemptType = 'black';

        return $guessData;
    }

    protected function handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $attemptType = 'white';

        if($game->attempts_left == 1) {
            $title = AppString::get('game.sudden_death', null, $chatLanguage);
            $text = AppString::get('game.sudden_death_info', null, $chatLanguage);
        } else if($game->attempts_left <= 0) {
            return $this->getWinningGuessData($game, 'x', $chatLanguage, 'rounds_over');
        } else {
            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();
        }
        
        //skip
        $game->nextStatusCoop();

        $guessData = new GuessData($title, $attemptType, $text);

        return $guessData;
    }

    protected function getEmojis(Game $game) {
        return [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')]
        ];
    }

    protected function handleMessage(Update $update, User $user, GameCard $card, Game $game, $emoji, BotApi $bot, bool $isGuessCorrect) {
        $emoji = $this->getEmojiToHandleMessage($emoji);
        $string = $this->getStringToHandleMessage($isGuessCorrect);
        $chatLanguage = $game->creator->language;
        if($update->isType(Update::MESSAGE)) {
            $mention = AppString::get('game.mention', [
                'name' => $user->name,
                'id' => $user->id
            ], $chatLanguage, true);
            $text = AppString::get('game.'.$string, [
                'user' => $mention,
                'card' => AppString::parseMarkdownV2($card->text).' '.$emoji
            ], $chatLanguage);
            $bot->tryToDeleteMessage($update->getChatId(), $update->getMessageId());
            try {
                $bot->sendMessage($game->creator->id, $text, 'MarkdownV2');
                $bot->sendMessage($game->getPartner()->id, $text, 'MarkdownV2');
            } catch(Exception $e) {}
        }
    }

    protected function getWinningGuessData(Game $game, string $winner, string $chatLanguage, string $text = 'win_color') {
        if($winner == 'a') {
            return parent::getWinningGuessData($game, $winner, $chatLanguage, $text);
        }
        
        $guessData = new GuessData;
        $color = $game->getColor('a');
        $guessData->title = AppString::get('game.lost', [
            'team' => AppString::get('color.'.$color)
        ], $chatLanguage);
        $guessData->text = AppString::get('game.'.$text, null, $chatLanguage);
        $guessData->winner = $winner;

        return $guessData;
    }

}