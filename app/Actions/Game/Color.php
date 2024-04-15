<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\User;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\QueryResult\Article;
use TelegramBot\Api\Types\Inline\InputMessageContent\Text;

class Color implements Action {

    public function run($update, BotApi $bot) : Void {
        $results = [];
        foreach(Game::COLORS as $color => $emoji) {
            $title = $emoji.' '.AppString::get('color.',$color);
            $data = CDM::toString([
                CDM::EVENT => CDM::CHANGE_COLOR,
                CDM::TEXT => $color
            ]);
            $messageContent = new Text($title);
            $results[] = new Article($data, $title, null, null, null, null, $messageContent);
        }

        $bot->answerInlineQuery($update->getId(), $results, 10);
    }

}
