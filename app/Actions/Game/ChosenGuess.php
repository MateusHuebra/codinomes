<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
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
            $text = mb_strtoupper($update->getMessageText(), 'UTF-8');
            $card = GameCard::where('text', $text)->first();
            if(!$card) {
                return;
            }
        } else if($update->isType(Update::CALLBACK_QUERY)) {
            $data = CDM::toArray($update->getResultId());
            $card = GameCard::find($data[CDM::NUMBER]);
        }

        if($card->revealed) {
            return;
        }
        $card->revealed = true;
        $card->save();
        
        switch ($card->team) {
            case 'a':
                $emoji = Game::COLORS[$game->color_a];
                break;
            
            case 'b':
                $emoji = Game::COLORS[$game->color_b];
                break;
            
            default:
            $emoji = Game::COLORS['white'];
                break;
        }
        $game->addToHistory('>'.$emoji.' '.mb_strtolower($card->text, 'UTF-8'));

        //correct guess
        if($card->team == $user->team) {
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
                $caption = new Caption($title, $text);

                Table::send($game, $bot, $caption,$card->id, $user->team);
                
            //next
            } else if($game->attempts_left===null || $game->attempts_left >= 0) {
                $title = AppString::get('game.correct', null, $chatLanguage).' '.$game->getLastHint();
                $text = AppString::get('game.history', null, $chatLanguage);
                $caption = new Caption($title, $text);
                
                Table::send($game, $bot, $caption,$card->id);

            //skip
            } else {
                $game->nextStatus($user);

                $title = AppString::get('game.correct', null, $chatLanguage).' '.$game->getLastHint();
                $text = AppString::get('game.history', null, $chatLanguage);
                $caption = new Caption($title, $text);

                Table::send($game, $bot, $caption, $card->id);
            }

        //black card
        } else if($card->team == 'x') {
            $color = ($user->getEnemyTeam() == 'a') ? $game->color_a : $game->color_b;
            $title = AppString::get('game.win', [
                'team' => AppString::get('color.'.$color)
            ], $chatLanguage);
            $text = AppString::get('game.win_black', null, $chatLanguage);
            $caption = new Caption($title, $text);

            Table::send($game, $bot, $caption,$card->id, $user->getEnemyTeam());
        
        //incorrect guess
        } else {
            $cardsLeft = $game->cards->where('team', $user->getEnemyTeam())->where('revealed', false)->count();
            
            //won
            if($cardsLeft <= 0) {
                $color = ($user->getEnemyTeam() == 'a') ? $game->color_a : $game->color_b;
                $title = AppString::get('game.win', [
                    'team' => AppString::get('color.'.$color)
                ], $chatLanguage);
                $text = AppString::get('game.win_color', null, $chatLanguage);
                $caption = new Caption($title, $text);

                Table::send($game, $bot, $caption,$card->id, $user->getEnemyTeam());
            
            //skip
            } else {
                $game->nextStatus($user);

                $title = AppString::get('game.incorrect', null, $chatLanguage).' '.$game->getLastHint();
                $text = AppString::get('game.history', null, $chatLanguage);
                $caption = new Caption($title, $text);
    
                Table::send($game, $bot, $caption, $card->id);
            }
        }

    }

}