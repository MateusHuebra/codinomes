<?php

namespace App\UpdateHandlers;

use App\Actions\Game\Hint;
use App\Actions\Game\Guess;
use App\Models\Game;
use App\Models\User;

class InlineQuery implements UpdateHandler {

    public function getAction($update) {
        $event = $this->getEvent($update);

        if($event === 'hint') {
            return new Hint;

        } else if($event === 'guess') {
            return new Guess;

        }

    }

    private function getEvent($update) {
        $user = User::find($update->getFrom()->getId());
        if($user && $user->game_id) {
            $game = Game::find($user->game_id);
            if($game->status=='master_a' && $user->team=='a' && $user->role=='master') {
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