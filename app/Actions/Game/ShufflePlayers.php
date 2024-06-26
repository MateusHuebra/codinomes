<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\Game\Menu;
use Illuminate\Database\Eloquent\Collection;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class ShufflePlayers implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $user = $update->findUser();
        if(!$chat || !$user) {
            return;
        }
        
        if(!$game = $chat->currentGame()) {
            return;
        }

        if(!$game->hasPermission($user, $bot)) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $this->chat_id, 'error.admin_only');
            return;
        }
        
        $users = $game->users->shuffle();
        
        $users = $this->updateUser($users, 'a', 'master', $game->id);
        $users = $this->updateUser($users, 'b', 'master', $game->id);

        $count = $users->count();
        for ($i=0; $i < $count; $i++) {
            $team = ($i % 2 == 0) ? 'a' : 'b';
            $users = $this->updateUser($users, $team, 'agent', $game->id);
        }

        Menu::send($game, $bot);
    }

    private function updateUser(Collection $users, string $team, string $role, int $gameId) {
        $user = $users->first();
        $user->games()->syncWithoutDetaching([
            $gameId => [
                'team' => $team,
                'role' => $role
            ]
        ]);
        $user->team = $team;
        $user->role = $role;
        $key = $users->search($user);
        $users->forget($key);
        return $users;
    }

}