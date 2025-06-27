<?php

namespace App\Actions\Game\ChosenGuess;

use App\Actions\Action;
use App\Models\Game;
use App\Models\GameTeamColor;
use App\Models\TeamColor;

class Triple extends Classic implements Action {

    protected function getEmojis(Game $game) {
        return [
            'w' => GameTeamColor::COLORS['white'],
            'x' => GameTeamColor::COLORS['black'],
            'a' => TeamColor::where('shortname', $game->getColor('a'))->first()->emoji,
            'b' => TeamColor::where('shortname', $game->getColor('b'))->first()->emoji,
            'c' => TeamColor::where('shortname', $game->getColor('c'))->first()->emoji
        ];
    }

}