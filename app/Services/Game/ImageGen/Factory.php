<?php

namespace App\Services\Game\ImageGen;
use App\Models\Game;

class Factory {

    public static function build(string $gameMode) {
        if($gameMode == Game::FAST) {
            return new Fast;
        
        }
        
        return new Classic;
    }

}