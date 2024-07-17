<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\AppString;
use App\Services\ServerLog;
use TelegramBot\Api\BotApi;

class SendList implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('supergroup')) {
            $chat = $update->findChat();
            if (!$chat) {
                return;
            }
            $game = $chat->currentGame();
            
            if(!$game || $game->status != 'playing') {
                $text = AppString::get('error.no_game');

            } else {
                $text = $this->getPublicList($game);
            }

        
        } else if($update->isChatType('private')) {
            $user = $update->findUser();
            if (!$user) {
                return;
            }
            $game = $user->currentGame();

            if(!$game || $game->status != 'playing') {
                $text = AppString::get('error.no_game');

            } else if($game->mode == Game::COOP) {
                $text = $this->getPrivateList($game, $game->player->role);

            } else if($game->player->role == 'master') {
                if($game->role == 'master' && $game->team == $game->player->team) {
                    $text = $this->getPrivateList($game);
                    
                } else {
                    $text = AppString::get('error.your_turn_only');
                }

            } else {
                $text = $this->getPublicList($game);
            }

        } else {
            return;
        }
            
        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, true, true);
    }

    private function getPrivateList(Game $game, string $coopRole = null) {
        $emojis = [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')]
        ];
        if($game->mode != Game::COOP) {
            $emojis+= ['b' => Game::COLORS[$game->getColor('b')]];
        }
        if($game->mode == Game::TRIPLE) {
            $emojis+= ['c' => Game::COLORS[$game->getColor('c')]];
        }

        $text = '**>';
        $cardsToImplode = [];

        if($coopRole) {
            if(is_null($game->role)) {
                $teamField = $coopRole == 'agent' ? 'coop_team' : 'team';
                $revealedField = $coopRole == 'agent' ? 'coop_revealed' : 'revealed';
                $otherTeamField = $coopRole == 'master' ? 'coop_team' : 'team';
                $otherRevealedField = $coopRole == 'master' ? 'coop_revealed' : 'revealed';
                $cards = $game->cards()
                ->where($revealedField, false)
                ->where(function($query) use ($otherTeamField, $otherRevealedField) {
                    $query->where($otherRevealedField, false)
                          ->orWhere(function($query) use ($otherTeamField, $otherRevealedField) {
                              $query->where($otherRevealedField, true)
                                    ->where($otherTeamField, 'w');
                          });
                })
                ->orderBy($teamField)
                ->orderBy('position')
                ->get();
            } else {
                $teamField = $coopRole == 'master' ? 'coop_team' : 'team';
                $revealedField = $coopRole == 'master' ? 'coop_revealed' : 'revealed';
                $otherTeamField = $coopRole == 'agent' ? 'coop_team' : 'team';
                $otherRevealedField = $coopRole == 'agent' ? 'coop_revealed' : 'revealed';
                $cards = $game->cards()
                ->where($revealedField, false)
                ->where(function($query) use ($otherTeamField, $otherRevealedField) {
                    $query->where($otherRevealedField, false)
                          ->orWhere(function($query) use ($otherTeamField, $otherRevealedField) {
                              $query->where($otherRevealedField, true)
                                    ->where($otherTeamField, 'w');
                          });
                })
                ->orderBy($teamField)
                ->orderBy('position')
                ->get();
                $teamField = $coopRole == 'agent' ? 'coop_team' : 'team';
            }
            
        } else {
            $revealedField = 'revealed';
            $teamField = 'team';

            $cards = $game->cards()
                          ->where($revealedField, false)
                          ->orderByRaw("CASE WHEN $teamField = '".$game->player->team."' THEN 0 ELSE 1 END")
                          ->orderBy($teamField)
                          ->orderBy('position')
                          ->get();
        }
                      
        foreach ($cards as $card) {
            $cardsToImplode[] = $emojis[$card->$teamField].' '.AppString::parseMarkdownV2($card->text);
        }
        $text.= implode("\n>", $cardsToImplode).'||';
        return $text;
    }

    private function getPublicList(Game $game) {
        $text = '**>';
        $cards = $game->cards()
                      ->where('revealed', false)
                      ->orderBy('position')
                      ->get()
                      ->pluck('text')
                      ->toArray();
        foreach ($cards as $key => $card) {
            $cards[$key] = AppString::parseMarkdownV2($card);
        }
        $text.= implode("\n>", $cards).'||';

        return $text;
    }

}