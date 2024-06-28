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

        if($update->isType(Update::MESSAGE)) {
            $card = GameCard::where('game_id', $game->id)
                ->whereRaw('BINARY text = ?', [$update->getMessageText()])
                ->first();
            if(!$card) {
                return;
            }
        } else if($update->isType(Update::CHOSEN_INLINE_RESULT)) {
            $data = CDM::toArray($update->getResultId());
            $card = GameCard::where('game_id', $game->id)
                ->where('position', $data[CDM::NUMBER])
                ->first();
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

        //correct guess
        if($card->team == $player->team) {
            $guessData = $this->handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft, $player, $chatLanguage, $opponentCardsLeft);

        //black card
        } else if($card->team == 'x') {
            $guessData = $this->handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage);

        //incorrect guess
        } else {
            $guessData = $this->handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player);
        }

        $caption = $this->getCaption($game, $guessData);
        
        Table::send($game, $bot, $caption, $card->position, $guessData->winner);
        UserStats::addAttempt($game, $player->team, $guessData->attemptType, $bot);

    }

    protected function getEmojis(Game $game) {
        return [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')],
            'b' => Game::COLORS[$game->getColor('b')]
        ];
    }

    protected function getCaption($game, $guessData) {
        return new Caption($guessData->title, $guessData->title??null, 30, $game->mode == Game::EMOJI);
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

            $color = $game->getColor($player->team);
            $title = AppString::get('game.win', [
                'team' => AppString::get('color.'.$color)
            ], $chatLanguage);
            $text = AppString::get('game.win_color', null, $chatLanguage);
            $winner = $player->team;
            
        //next
        } else if($game->attempts_left===null || $game->attempts_left >= 0) {
            $title = AppString::get('game.correct', null, $chatLanguage);
            $text = $game->getLastHint();
            $winner = null;

        //skip
        } else {
            $game->nextStatus($user->getNextTeam());

            $title = AppString::get('game.correct', null, $chatLanguage);
            $text = $game->getLastHint();

            $winner = null;
        }

        return new GuessData($title, $attemptType, $text, $winner); 
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
            $game->nextStatus($user->getNextTeam());

            $title = AppString::get('game.incorrect', null, $chatLanguage);
            $text = $game->getLastHint();

            $winner = null;
        }

        return new GuessData($title, $attemptType, $text, $winner);
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

}