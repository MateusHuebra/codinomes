<?php

namespace App\Actions\Game\ChosenGuess;
use App\Models\Game;


class Factory {

    static function build($gameMode) {
        if($gameMode == Game::MYSTERY) {
            return new Mystery;
        
        } else if($gameMode == Game::EIGHTBALL) {
            return new EightBall;
        }
        
        return new Classic;
    }

}