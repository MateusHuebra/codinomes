<?php

namespace App\Console\Scheduled;

use App\Models\Game;
use App\Services\Telegram\BotApi;

class NotifyNewGames {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));

        for ($i = 0; $i < 60; $i++) {
            $games = Game::where('status', 'creating')->get();
            foreach ($games as $game) {
                $game->status = 'lobby';
                $game->save();
                $game->chat->notifiableUsers->notify($game, $bot);
            }

            sleep(1);
        }
        
    }

}