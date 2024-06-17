<?php

namespace App\Console\Scheduled;

use App\Models\Game;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use App\Services\Telegram\BotApi;
use Exception;

class CheckTurnLeftTime {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $now = strtotime('now');
        
        $games = Game::where('status', 'playing')->get();
        foreach ($games as $game) {
            $timer = $game->chat->timer;
            if($timer == null) {
                continue;
            }
            $time = strtotime($game->status_updated_at);
            if($now - $time >= (60*$game->chat->timer)) {
                try {
                    $bot->deleteMessage($game->chat_id, $game->message_id);
                } catch(Exception $e) {}
                if($game->role == 'master') {
                    $this->skipMaster($game, $bot);

                } else {
                    $this->skipAgent($game, $bot);
                }
                
            } else if ($now - $time >= (60*($timer-1))) {
                $this->warn($game, 1, $bot);

            } else if ($now - $time >= (60*($timer-2))) {
                $this->warn($game, 2, $bot);
                
            } else if ($timer > 3 && $now - $time >= (60*($timer-3))) {
                $this->warn($game, 3, $bot);
                
            } else if ($timer > 5 && $now - $time >= (60*($timer-5))) {
                $this->warn($game, 5, $bot);
                
            } else if ($timer > 7 && $now - $time >= (60*($timer-7))) {
                $this->warn($game, 7, $bot);
                
            } else if ($timer > 10 && $now - $time >= (60*($timer-10))) {
                $this->warn($game, 10, $bot);
                
            }
        }
    }

    private function skipMaster(Game $game, BotApi $bot) {
        $hint = AppString::get('error.no_hint').' ∞';
        $color = $game->getColor($game->team);
        $historyLine = Game::COLORS[$color].' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        
        $game->updateStatus('playing', $game->team, 'agent');
        $game->attempts_left = null;
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
        $otherTeam = $game->team == 'a' ? 'b' : 'a';
        if($game->mode == '8ball' && $game->cards->where('team', $otherTeam)->where('revealed', false)->count() == 0) {
            $game->updateStatus('playing', $otherTeam, 'agent');
            $game->setEightBallToHistory($game->users()->fromTeamRole($otherTeam, 'agent')->first()->player);

            $title = AppString::get('game.8ball', null, $game->chat->language);

        } else {
            $game->nextStatus($otherTeam);

            $title = AppString::get('time.out', null, $game->chat->language);
        }

        $caption = new Caption($title);
        Table::send($game, $bot, $caption);
    }

    private function warn(Game $game, int $time, BotApi $bot) {
        if($game->role == 'master') {
            $chatId = $game->users()->fromTeamRole($game->team, 'master')->first()->id;
        } else {
            $chatId = $game->chat_id;
        }
        if($time==1){
            $minute = AppString::get('time.minute');
        } else {
            $minute = AppString::get('time.minutes');}
        try {
            $bot->sendMessage($chatId, AppString::get('time.left', [
                'time' => $time,
                'format' => $minute
            ]));
        } catch(Exception $e) {}
    }

}