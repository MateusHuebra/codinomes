<?php

namespace App\Console\Scheduled;

use App\Actions\Info;
use App\Models\Chat;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use Carbon\Carbon;
use Exception;
use App\Actions\DidYouKnow as DidYouKnowAction;

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
        $keyboard = [];
        foreach(AppString::$allLanguages as $language) {
             $keyboard[$language] = DidYouKnowAction::getKeyboard($language);
        }

        foreach ($chats as $chat) {
            try {
                $text = DidYouKnowAction::getText($chat->language);
                $bot->sendMessage($chat->id, $text, 'MarkdownV2', false, null, $keyboard[$chat->language]);

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