<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\User;
use App\Models\UserStats;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenGuess implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->game;
        $chatLanguage = $game->chat->language;

        if(!(($game->status=='agent_a' && $user->team=='a' && $user->role=='agent') || ($game->status=='agent_b' && $user->team=='b' && $user->role=='agent'))) {
            return;
        }
        if($game->attempts_left < 0) {
            return;
        }

        if($update->isType(Update::MESSAGE)) {
            $text = $update->getMessageText();
            $card = GameCard::where('game_id', $game->id)
                ->whereRaw('BINARY text = ?', [$text])
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

        $emojis = [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->color_a],
            'b' => Game::COLORS[$game->color_b]
        ];
        $emoji = $emojis[$card->team];
        $game->addToHistory('  - '.$emoji.' '.mb_strtolower($card->text, 'UTF-8'));
        
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot);

        //correct guess
        if($card->team == $user->team) {
            $attemptType = 'ally';
            if($game->attempts_left!==null) {
                $game->attempts_left--;
            }
            $cardsLeft = $game->cards->where('team', $user->team)->where('revealed', false)->count();

            //won
            if($cardsLeft <= 0) {
                $color = ($user->team == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_color', null, $chatLanguage);

                $winner = $user->team;
                
            //next
            } else if($game->attempts_left===null || $game->attempts_left >= 0) {
                $title = AppString::get('game.correct', null, $chatLanguage).' '.$game->getLastHint();
                $text = AppString::get('game.history', null, $chatLanguage);

                $winner = null;

            //skip
            } else {
                $game->nextStatus($user);

                $title = AppString::get('game.correct', null, $chatLanguage).' '.$game->getLastHint();
                $text = AppString::get('game.history', null, $chatLanguage);

                $winner = null;
            }

        //black card
        } else if($card->team == 'x') {
            $attemptType = 'black';
            $color = ($user->getEnemyTeam() == 'a') ? $game->color_a : $game->color_b;
            $title = AppString::get('game.win', [
                'team' => AppString::get('color.'.$color)
            ], $chatLanguage);
            $text = AppString::get('game.win_black', null, $chatLanguage);

            $winner = $user->getEnemyTeam();
            
        //incorrect guess
        } else {
            $attemptType = $card->team == 'w' ? 'white' : 'opponent';
            $cardsLeft = $game->cards->where('team', $user->getEnemyTeam())->where('revealed', false)->count();
            
            //won
            if($cardsLeft <= 0) {
                $color = ($user->getEnemyTeam() == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_color', null, $chatLanguage);

                $winner = $user->getEnemyTeam();
            
            //skip
            } else {
                $game->nextStatus($user);

                $title = AppString::get('game.incorrect', null, $chatLanguage).' '.$game->getLastHint();
                $text = AppString::get('game.history', null, $chatLanguage);

                $winner = null;
            }
        }

        $caption = new Caption($title, $text);
        UserStats::addAttempt($game, $user->team, $attemptType);
        Table::send($game, $bot, $caption, $card->position, $winner);

    }

    private function handleMessage(Update $update, User $user, GameCard $card, Game $game, $emoji, BotApi $bot) {
        $chatLanguage = $game->chat->language;
        if($update->isType(Update::MESSAGE)) {
            $mention = AppString::get('game.mention', [
                'name' => $user->name,
                'id' => $user->id
            ], $chatLanguage, true);
            $text = AppString::get('game.attempted', [
                'user' => $mention,
                'card' => AppString::parseMarkdownV2($card->text).' '.$emoji
            ], $chatLanguage);
            $bot->tryToDeleteMessage($update->getChatId(), $update->getMessageId());
            try {
                $bot->sendMessage($game->chat_id, $text, 'MarkdownV2');
            } catch(Exception $e) {}
        }
    }

}