<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;

class Triple extends Classic implements Action {

    protected function getEmojis(Game $game) {
        return [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')],
            'b' => Game::COLORS[$game->getColor('b')],
            'c' => Game::COLORS[$game->getColor('c')]
        ];
    }

}