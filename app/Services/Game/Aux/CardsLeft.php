<?php

namespace App\Services\Game\Aux;

use App\Models\Game;
use App\Models\UserAchievement;
use TelegramBot\Api\BotApi;
use Illuminate\Database\Eloquent\Collection;

class CardsLeft {

    public $A;
    public $B;
    public $C;
    public $attemptsLeft;

    public function __construct($A, $B = null, $C = null, $attemptsLeft = null) {
        $this->A = $A;
        $this->B = $B;
        $this->C = $C;
        $this->attemptsLeft = $attemptsLeft;
    }

    public static function get(Collection $cards, Game $game, BotApi $bot) {
        $leftA = null;
        $leftB = null;
        $leftC = null;
        $attemptsLeft = null;
        if($game->mode == Game::COOP) {
            $leftA = 15 - $cards->filter(function ($card) {
                return ($card->team == 'a' && $card->revealed == true) ||
                       ($card->coop_team == 'a' && $card->coop_revealed == true);
            })->count();
            $attemptsLeft = $game->attempts_left;
        } else {
            $leftA = $cards->where('team', 'a')
                           ->where('revealed', false)
                           ->count();
            $leftB = $cards->where('team', 'b')
                           ->where('revealed', false)
                           ->count();
        }
        if($game->mode == Game::TRIPLE) {
            $leftC = $cards->where('team', 'c')
                           ->where('revealed', false)
                           ->count();
            if($leftA==6 && $leftB==6 && $leftC==6) {
                UserAchievement::add($game->users, 'sixsixsix', $bot, $game->chat_id);
            }
        } else if(($leftA==1 && $leftB==7) || ($leftA==7 && $leftB==1)) {
            UserAchievement::add($game->users, 'seven_one', $bot, $game->chat_id);
        }
        return new self($leftA, $leftB, $leftC, $attemptsLeft);
    }

}