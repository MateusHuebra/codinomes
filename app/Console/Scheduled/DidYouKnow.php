<?php

namespace App\Console\Scheduled;

use App\Models\Chat;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use Carbon\Carbon;
use Exception;

class DidYouKnow {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $oneWeekAgo = Carbon::now()->subWeek();
        $chats = Chat::where('actived', true)
                        ->whereHas('games', function ($query) use ($oneWeekAgo) {
                            $query->where('created_at', '>=', $oneWeekAgo);
                        })
                        ->get();

        foreach ($chats as $chat) {
            try {
                $text = '*'.AppString::get('did_you_know.title', null, $chat->language).'*';
                $text.= PHP_EOL.PHP_EOL.AppString::getParsed('did_you_know.text', null, $chat->language);
                $bot->sendMessage($chat->id, $text, 'MarkdownV2');

            } catch (Exception $e) {
                if(in_array($e->getMessage(), [
                    'Bad Request: chat not found',
                    'Bad Request: group chat was upgraded to a supergroup chat'
                ])) {
                    $chat->actived = false;
                    $chat->save();
                    
                } else {
                    $title = $chat->title;
                    $bot->sendMessage(env('TG_MY_ID'), $title.' didyouknow error: '.$e->getMessage());
                }

            }
        }
    }

}