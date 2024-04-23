<?php

namespace App\Console\Scheduled;

use App\Models\Game;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;

class CheckTurnLeftTime {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $now = strtotime('now');
        
        $games = Game::all();
        foreach ($games as $game) {
            if($game->status == 'creating') {
                continue;
            }
            $time = strtotime($game->status_updated_at);
            if($now - $time >= (60*5)) {
                try {
                    $bot->deleteMessage($game->chat_id, $game->message_id);
                } catch(Exception $e) {}
                if($game->status == 'master_a' || $game->status == 'master_b') {
                    $this->skipMaster($game, $bot);

                } else {
                    $this->skipAgent($game, $bot);
                }
                
            } else if ($now - $time >= (60*3)) {
                $this->warn($game->chat_id, 3, $bot);

            } else if ($now - $time >= (60*2)) {
                $this->warn($game->chat_id, 2, $bot);
                
            } else if ($now - $time >= (60*1)) {
                $this->warn($game->chat_id, 1, $bot);
                
            }
        }
    }

    private function skipMaster(Game $game, BotApi $bot) {
        $hint = AppString::get('error.no_hint');
        $color = ($game->status=='master_a') ? $game->color_a : $game->color_b;
        $historyLine = Game::COLORS[$color].' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        
        $game->updateStatus('agent_'.substr($game->status, 0, -1));
        $game->attempts_left = 0;
        $game->save();

        $title = AppString::get('time.out');
        $text = AppString::get('game.history');
        $caption = new Caption($title, $text);
        
        try {
            $bot->sendMessage($game->chat_id, $historyLine);
        } catch(Exception $e) {}
        Table::send($game, $bot, $caption);
    }

    private function skipAgent(Game $game, BotApi $bot) {
        $game->updateStatus('master_'.substr($game->status, 0, -1));
        $game->attempts_left = null;
        $game->save();

        $title = AppString::get('time.out');
        $text = AppString::get('game.history');
        $caption = new Caption($title, $text);

        Table::send($game, $bot, $caption);
    }

    private function warn($chatId, int $time, BotApi $bot) {
        if($time==1){
            $minute = AppString::get('time.minute');
        } else {
            $minute = AppString::get('time.minutes');}
        try {
            $bot->sendMessage($chatId, AppString::get('time.left', [
                'time' => $time,
                'minute' => $minute
            ]));
        } catch(Exception $e) {}
    }

}