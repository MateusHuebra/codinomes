<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use Carbon\Carbon;

class Status implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $gameStatusCounts = Game::select(DB::raw('status, count(*) as count'))
                                ->whereDate('created_at', Carbon::today())
                                ->groupBy('status')
                                ->get();
        $playingCount = $gameStatusCounts->firstWhere('status', 'playing')->count ?? 0;
        $lobbyCount = $gameStatusCounts->firstWhere('status', 'lobby')->count ?? 0;
        $endedCount = $gameStatusCounts->firstWhere('status', 'ended')->count ?? 0;

        $text = AppString::get('stats.status', [
            'playingCount' => $playingCount,
            'lobbyCount' => $lobbyCount,
            'endedCount' => $endedCount
        ]);

        $bot->sendMessage($update->getChatId(), $text, null, false, $update->getMessageId(), null, false, null, null, true);
    }

}