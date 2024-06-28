<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserStats;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\GuessData;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Classic implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->currentGame();
        $player = $game->player;
        $chatLanguage = $game->chat->language;

        if(!($game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team)) {
            return;
        }
        if($game->attempts_left < 0) {
            return;
        }

        $card = $this->getChosenCard($update, $game->id);

        if(!$card) {
            return;
        }
        if($card->revealed) {
            return;
        }
        $card->revealed = true;
        $card->save();

        $emojis = $this->getEmojis($game);
        $emoji = $emojis[$card->team];

        if($game->attempts_left!==null) {
            $game->attempts_left--;
        }
        $game->addToHistory('  - '.$emoji.' '.mb_strtolower($card->text, 'UTF-8'));

        $cardsLeft = $game->cards->where('team', $player->team)->where('revealed', false)->count();
        $opponentCardsLeft = $game->cards->where('team', $user->getEnemyTeam())->where('revealed', false)->count();

        if($card->team == $player->team) {
            $guessData = $this->handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft, $player, $chatLanguage, $opponentCardsLeft);

        } else if($card->team == 'x') {
            $guessData = $this->handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage);

        } else {
            $guessData = $this->handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player);
        }

        $caption = $this->getCaption($guessData, $game);
        
        Table::send($game, $bot, $caption, $card->position, $guessData->winner);
        UserStats::addAttempt($game, $player->team, $guessData->attemptType, $bot);

    }

    protected function getChosenCard(Update $update, int $gameId) {
        if($update->isType(Update::MESSAGE)) {
            return GameCard::where('game_id', $gameId)
                ->whereRaw('BINARY text = ?', [$update->getMessageText()])
                ->first();
            
        } else if($update->isType(Update::CHOSEN_INLINE_RESULT)) {
            $data = CDM::toArray($update->getResultId());
            return GameCard::where('game_id', $gameId)
                ->where('position', $data[CDM::NUMBER])
                ->first();
        }
        return null;
    }

    protected function getEmojis(Game $game) {
        return [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')],
            'b' => Game::COLORS[$game->getColor('b')]
        ];
    }

    protected function getCaption(GuessData $guessData, Game $game) {
        return new Caption($guessData->title, $guessData->text??null, 30, false);
    }

    protected function handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft,$player, $chatLanguage, $opponentCardsLeft) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, true);
        $attemptType = 'ally';
        //won
        if($cardsLeft <= 0) {
            if($game->mode == Game::FAST && $game->countLastStreak() == $game->cards->where('team', $player->team)->count()) {
                $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
                UserAchievement::add($agents, 'hurry', $bot, $game->chat_id);
            }

            $guessData = $this->getWinningGuessData($game, $player->team, $chatLanguage);
            $guessData->attemptType =$attemptType;
            
        //next
        } else if($game->attempts_left===null || $game->attempts_left >= 0) {
            $title = AppString::get('game.correct', null, $chatLanguage);
            $text = $game->getLastHint();

            $guessData = new GuessData($title, $attemptType, $text);

        //skip
        } else {
            $game->nextStatus($user->getNextTeam());

            $title = AppString::get('game.correct', null, $chatLanguage);
            $text = $game->getLastHint();

            $guessData = new GuessData($title, $attemptType, $text);
        }

        return $guessData; 
    }

    protected function handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $attemptType = 'black';
        $color = $game->getColor($user->getEnemyTeam());
        $title = AppString::get('game.win', [
            'team' => AppString::get('color.'.$color)
        ], $chatLanguage);
        $text = AppString::get('game.win_black', null, $chatLanguage);
        $winner = $user->getEnemyTeam();
        
        UserAchievement::checkBlackCard($game, $cardsLeft, $player, $bot);

        return new GuessData($title, $attemptType, $text, $winner);
    }

    protected function handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player) : GuessData {
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot, false);
        $attemptType = $card->team == 'w' ? 'white' : 'opponent';
        //won
        if($game->cards->where('team', $card->team)->where('revealed', false)->count() <= 0) {
            $guessData = $this->getWinningGuessData($game, $card->team, $chatLanguage);
            $guessData->attemptType = $attemptType;
            
            $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
            UserAchievement::add($agents, 'impostor', $bot, $game->chat_id);
        
        //skip
        } else {
            $game->nextStatus($user->getNextTeam());

            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();

            $guessData = new GuessData($title, $attemptType, $text);
        }

        return $guessData;
    }

    protected function handleMessage(Update $update, User $user, GameCard $card, Game $game, $emoji, BotApi $bot, bool $isGuessCorrect) {
        $emoji = $this->getEmojiToHandleMessage($emoji);
        $string = $this->getStringToHandleMessage($isGuessCorrect);
        $chatLanguage = $game->chat->language;
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
                $bot->sendMessage($game->chat_id, $text, 'MarkdownV2');
            } catch(Exception $e) {}
        }
    }

    protected function getEmojiToHandleMessage(string $emoji) {
        return $emoji;
    }

    protected function getStringToHandleMessage(bool $isGuessCorrect) {
        return $isGuessCorrect ? 'attempted_correct' : 'attempted';
    }

    protected function getWinningGuessData(Game $game, string $winner, string $chatLanguage) {
        $guessData = new GuessData;
        $color = $game->getColor($winner);
        $guessData->title = AppString::get('game.win', [
            'team' => AppString::get('color.'.$color)
        ], $chatLanguage);
        $guessData->text = AppString::get('game.win_color', null, $chatLanguage);
        $guessData->winner = $winner;

        return $guessData;
    }

}