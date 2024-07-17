<?php

namespace App\Actions\Game\ChosenGuess;
use App\Models\Game;


class Factory {

    static function build($gameMode) {
        if($gameMode == Game::EIGHTBALL) {
            return new EightBall;
        
        } else if($gameMode == Game::EMOJI) {
            return new Emoji;
        
        } else if($gameMode == Game::MYSTERY) {
            return new Mystery;
        
        } else if($gameMode == Game::TRIPLE) {
            return new Triple;
        
        } else if($gameMode == Game::COOP) {
            return new Coop;
        }
        
        return new Classic;
    }

}