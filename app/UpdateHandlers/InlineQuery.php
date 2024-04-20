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
        $game = $user->game;
        if($user && $game) {
            if($game->status=='creating' && $user->role=='master') {
                return null;//'color';
            } else if($game->status=='master_a' && $user->team=='a' && $user->role=='master') {
                return 'hint';
            } else if($game->status=='master_b' && $user->team=='b' && $user->role=='master') {
                return 'hint';
            } else if($game->status=='agent_a' && $user->team=='a' && $user->role=='agent') {
                return 'guess';
            } else if($game->status=='agent_b' && $user->team=='b' && $user->role=='agent') {
                return 'guess';
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

}