<?php

namespace App\UpdateHandlers;

use App\Actions\Game\Color;
use App\Actions\Game\Hint;
use App\Actions\Game\Guess;
use App\Models\Game;
use App\Models\User;

class InlineQuery implements UpdateHandler {

    public function getAction($update) {
        $event = $this->getEvent($update);

        if($event === 'color') {
            return new Color;

        } else if($event === 'hint') {
            return new Hint;

        } else if($event === 'guess') {
            return new Guess;

        }

    }

    private function getEvent($update) {
        $user = $update->findUser();
        $game = $user->currentGame();
        if($user && $game) {
            $player = $game->player;
            if(in_array($game->status, ['creating', 'lobby'])) {
                return null;
            //} else if($game->role == 'master' && $player->role == 'master' && $player->team == $game->team) {
            //    return 'hint';
            } else if(
                ($game->mode != Game::COOP && $game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team)
                ||
                ($game->mode == Game::COOP && ($game->role == $player->role || $game->attempts_left == 0))
            ) {
                return 'guess';
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

}