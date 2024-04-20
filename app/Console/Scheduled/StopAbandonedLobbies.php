<?php

namespace App\Console\Scheduled;

use App\Models\Game;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\BotApi;

class StopAbandonedLobbies {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $now = strtotime('now');
        
        $games = Game::all();
        foreach ($games as $game) {
            if($game->status != 'creating') {
                continue;
            }
            $time = strtotime($game->status_updated_at);
            if($now - $time >= 1200) {
                try {
                    $bot->deleteMessage($game->chat_id, $game->message_id);
                    $bot->sendMessage($game->chat_id, AppString::get('game.stopped_by_time'));
                } catch(Exception $e) {}
                $game->stop();
            }
        }
    }

}