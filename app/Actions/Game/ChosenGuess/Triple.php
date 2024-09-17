<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;
use App\Models\GameTeamColor;

class Triple extends Classic implements Action {

    protected function getEmojis(Game $game) {
        return [
            'w' => GameTeamColor::COLORS['white'],
            'x' => GameTeamColor::COLORS['black'],
            'a' => GameTeamColor::COLORS[$game->getColor('a')],
            'b' => GameTeamColor::COLORS[$game->getColor('b')],
            'c' => GameTeamColor::COLORS[$game->getColor('c')]
        ];
    }

}