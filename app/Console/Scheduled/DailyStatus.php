<?php

namespace App\Console\Scheduled;

use App\Services\AppString;
use TelegramBot\Api\BotApi;
use Carbon\Carbon;
use App\Models\Game;

class DailyStatus {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $yesterday = Carbon::yesterday();
        $endedCount = Game::where('status', 'ended')
                                ->whereDate('created_at', $yesterday)
                                ->count();

        $text = AppString::get('stats.daily', [
            'date' => $yesterday->format('d/m/Y'),
            'endedCount' => $endedCount
        ]);

        $bot->sendMessage(env('TG_MY_ID'), $text, 'MarkdownV2');
    }

}