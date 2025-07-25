<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\GameTeamColor;
use App\Models\TeamColor;
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
        $chatLanguage = ($game->chat??$game->creator)->language;
        
        if(
            !($game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team)
            &&
            !($game->mode == Game::COOP && ($player->role == $game->role || $game->attempts_left == 0))
        ) {
            return;
        }
        if($game->attempts_left < 0) {
            return;
        }

        $card = $this->getChosenCard($update, $game->id);
        if(!$card) {
            return;
        }
        if(
            ($game->mode != Game::COOP && $card->revealed)
            || 
            ($game->mode == Game::COOP && $game->role == 'master'
                && (
                    $card->coop_revealed
                    || ($card->revealed && $card->team != 'w')
                )
            )
            || 
            ($game->mode == Game::COOP && $game->role == 'agent'
                && (
                    $card->revealed
                    || ($card->coop_revealed && $card->coop_team != 'w')
                )
            )
        ) {
            return;
        }

        $emojis = $this->getEmojis($game);
        $emoji = $emojis[$player->role == 'agent' ? $card->team : $card->coop_team];

        $game->addToHistory('  - '.$emoji.' '.mb_strtolower($card->text, 'UTF-8'));

        $guessData = $this->handle($update, $game, $user, $player, $card, $emoji, $bot, $chatLanguage);

        $caption = $this->getCaption($guessData, $game);
        
        Table::send($game, $bot, $caption, $card->position, $guessData->winner);
        UserStats::addAttempt($game, $player->team, $guessData->attemptType, $bot);
    }

    protected function handle(Update $update, Game $game, User $user, $player, $card, $emoji, BotApi $bot, $chatLanguage) {
        $card->revealed = true;
        $card->save();

        if($game->attempts_left!==null) {
            $game->attempts_left--;
        }
        $cardsLeft = $game->cards->where('team', $player->team)->where('revealed', false)->count();
        $opponentCardsLeft = $game->cards->where('team', $user->getEnemyTeam())->where('revealed', false)->count();

        if($card->team == $player->team) {
            return $this->handleCorrectGuess($update, $user, $card, $game, $emoji, $bot, $cardsLeft, $player, $chatLanguage, $opponentCardsLeft);

        } else if($card->team == 'x') {
            return $this->handleBlackGuess($game, $cardsLeft, $update, $user, $card, $emoji, $bot, $player, $chatLanguage);

        } else {
            return $this->handleIncorrectGuess($update, $game, $card, $user, $emoji, $bot, $chatLanguage, $opponentCardsLeft, $player);
        }

        if(in_array($card->text, \App\Services\Game\ImageGen\Classic::EASTER_EGGS)) {
            $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
            UserAchievement::add($agents, 'egg_hunt', $bot, $game->chat_id);
        }
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
            'w' => GameTeamColor::COLORS['white'],
            'x' => GameTeamColor::COLORS['black'],
            'a' => TeamColor::where('shortname', $game->getColor('a'))->first()->emoji,
            'b' => TeamColor::where('shortname', $game->getColor('b'))->first()->emoji,
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
            /*
            if($game->mode == Game::FAST && $game->countLastStreak() == $game->cards->where('team', $player->team)->count()) {
                $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
                UserAchievement::add($agents, 'hurry', $bot, $game->chat_id);
            }
            */

            $guessData = $this->getWinningGuessData($game, $player->team, $chatLanguage);
            $guessData->attemptType = $attemptType;
            
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
        $guessData = $this->getWinningGuessData($game, $user->getEnemyTeam(), $chatLanguage, 'win_black');
        $guessData->attemptType = 'black';
        
        UserAchievement::checkBlackCard($game, $cardsLeft, $player, $bot);

        return $guessData;
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

    protected function getWinningGuessData(Game $game, string $winner, string $chatLanguage, string $text = 'win_color') {
        $guessData = new GuessData;
        $color = $game->getColor($winner);
        $guessData->title = AppString::get('game.win', [
            'team' => AppString::get('color.'.$color)
        ], $chatLanguage);
        $guessData->text = AppString::get('game.'.$text, null, $chatLanguage);
        $guessData->winner = $winner;

        return $guessData;
    }

}