<?php

namespace App\Console\Scheduled;

use App\Models\Game;
use App\Services\AppString;
use Exception;
use App\Services\Telegram\BotApi;

class StopAbandonedLobbies {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $now = strtotime('now');
        
        $games = Game::where('status', 'creating')->get();
        foreach ($games as $game) {
            $time = strtotime($game->status_updated_at);
            if($now - $time >= 600*1.5) {
                if($game->start($bot)) {
                    return;
                }
                try {
                    $bot->sendMessage($game->chat_id, AppString::get('game.stopped_by_time'));
                } catch(Exception $e) {}
                $game->stop($bot, true);
            }
        }
    }

}