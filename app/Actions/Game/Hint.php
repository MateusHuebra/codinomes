<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\QueryResult\Article;
use TelegramBot\Api\Types\Inline\InputMessageContent\Text;

class Hint implements Action {

    public function run($update, BotApi $bot) {
        $query = $update->getQuery();
        $results = [];
        if(preg_match('/^([A-z]{1,16}) ([0-9])$/', $query, $matches)) {
            $title = AppString::get('game.confirm_hint');
            $desc = $query;
            $messageContent = new Text(AppString::get('game.hint_sended'));
        } else {
            $title = AppString::get('error.wrong_hint_format_title');
            $desc = AppString::get('error.wrong_hint_format_desc');
            $messageContent = new Text($title);
        }

        $results[] = new Article(0, $title, $desc, null, null, null, $messageContent);
        $bot->answerInlineQuery($update->getId(), $results, 10);
    }

}