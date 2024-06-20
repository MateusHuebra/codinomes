<?php

namespace App\Console\Scheduled;

use App\Models\Chat;
use App\Models\Game;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use Exception;

class DidYouKnow {

    public function __invoke()
    {
        $bot = new BotApi(env('TG_TOKEN'));
        $chats = Chat::where('actived', true)->get();

        foreach ($chats as $chat) {
            try {
                $text = '*'.AppString::get('did_you_know.title', null, $chat->language).'*';
                $text.= PHP_EOL.PHP_EOL.AppString::getParsed('did_you_know.text', null, $chat->language);
                $bot->sendMessage($chat->id, $text);

            } catch (Exception $e) {
                $title = $chat->title;
                $bot->sendMessage(env('TG_MY_ID'), $title.' didyouknow error: '.$e->getMessage());
            }
        }
    }

}