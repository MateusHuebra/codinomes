<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\User;
use App\Models\UserAchievement;
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

        $emojis = [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->color_a],
            'b' => Game::COLORS[$game->color_b]
        ];
        $emoji = $emojis[$card->team];

        if($game->attempts_left!==null) {
            $game->attempts_left--;
        }
        $game->addToHistory('  - '.$emoji.' '.mb_strtolower($card->text, 'UTF-8'));
        
        $this->handleMessage($update, $user, $card, $game, $emoji, $bot);

        $cardsLeft = $game->cards->where('team', $player->team)->where('revealed', false)->count();
        $opponentCardsLeft = $game->cards->where('team', $user->getEnemyTeam())->where('revealed', false)->count();

        //correct guess
        if($card->team == $player->team) {
            $attemptType = 'ally';
            //won
            if($cardsLeft <= 0 && $game->mode != '8ball') {
                $color = ($player->team == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_color', null, $chatLanguage);
                $winner = $player->team;
                
            //next
            } else if($game->attempts_left===null || $game->attempts_left >= 0) {
                if($game->mode == '8ball' && $opponentCardsLeft == 0) {
                    $title = AppString::get('game.8ball', null, $chatLanguage);

                } else {
                    $title = AppString::get('game.correct', null, $chatLanguage);
                    $text = $game->getLastHint();
                }
                $winner = null;

            //skip
            } else {
                if($game->mode == '8ball' && $opponentCardsLeft == 0) {
                    $game->updateStatus('playing', $user->getEnemyTeam(), 'agent');

                    $title = AppString::get('game.8ball', null, $chatLanguage);

                } else {
                    $game->nextStatus($user);

                    $title = AppString::get('game.correct', null, $chatLanguage);
                    $text = $game->getLastHint();
                }

                $winner = null;
            }

        //black card
        } else if($card->team == 'x') {
            if($game->mode == '8ball' && $cardsLeft == 0) {
                $color = ($player->team == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_color', null, $chatLanguage);
                $winner = $player->team;

            } else {
                $attemptType = 'black';
                $color = ($user->getEnemyTeam() == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_black', null, $chatLanguage);
                $winner = $user->getEnemyTeam();
                
                UserAchievement::checkBlackCard($game, $cardsLeft, $player, $bot);
            }

        //incorrect guess
        } else {
            $attemptType = $card->team == 'w' ? 'white' : 'opponent';
            
            //won
            if($opponentCardsLeft <= 0 && $game->mode != '8ball') {
                $color = ($user->getEnemyTeam() == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_color', null, $chatLanguage);
                $winner = $user->getEnemyTeam();
                
                $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
                UserAchievement::add($agents, 'impostor', $bot, $game->chat_id);
            
            //skip
            } else {
                if($game->mode == '8ball' && $opponentCardsLeft == 0) {
                    $game->updateStatus('playing', $user->getEnemyTeam(), 'agent');

                    $title = AppString::get('game.8ball', null, $chatLanguage);

                } else {
                    if($game->mode != 'ghost' || $game->attempts_left < 0) {
                        $game->nextStatus($user);
                    }

                    $title = AppString::get('game.incorrect', null, $chatLanguage);
                    $text = $game->getLastHint();
                }

                $winner = null;
            }
        }

        if($game->mode == 'ghost' && $winner === null) {
            $caption = new Caption($game->getLastHint(), null, 50);
        } else {
            $caption = new Caption($title, $text??null);
        }

        Table::send($game, $bot, $caption, $card->position, $winner);
        UserStats::addAttempt($game, $player->team, $attemptType, $bot);

    }

    private function handleMessage(Update $update, User $user, GameCard $card, Game $game, $emoji, BotApi $bot) {
        $emoji = ($game->mode == 'ghost') ? '❔' : $emoji;
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