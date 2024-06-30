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

    public function __construct($A, $B = null, $C = null) {
        $this->A = $A;
        $this->B = $B;
        $this->C = $C;
    }

    public static function get(Collection $cards, Game $game, BotApi $bot) {
        $leftA = $cards->where('team', 'a')->where('revealed', false)->count();
        $leftB = null;
        $leftC = null;
        if($game->mode != Game::COOP) {
            $leftB = $cards->where('team', 'b')->where('revealed', false)->count();
        }
        if($game->mode == Game::TRIPLE) {
            $leftC = $cards->where('team', 'c')->where('revealed', false)->count();
            if($leftA==6 && $leftB==6 && $leftC==6) {
                UserAchievement::add($game->users, 'sixsixsix', $bot, $game->chat_id);
            }
        } else if(($leftA==1 && $leftB==7) || ($leftA==7 && $leftB==1)) {
            UserAchievement::add($game->users, 'seven_one', $bot, $game->chat_id);
        }
        return new self($leftA, $leftB, $leftC);
    }

}