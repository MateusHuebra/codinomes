<?php

namespace App\Services\Game\ImageGen;
use App\Models\Game;

class Factory {

    public static function build(string $gameMode) {
        if($gameMode == Game::FAST) {
            return new Fast;
        
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