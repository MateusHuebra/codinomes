<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\User;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\QueryResult\Article;
use TelegramBot\Api\Types\Inline\InputMessageContent\Text;
use App\Services\CallbackDataManager as CDM;

class Hint implements Action {

    public function run($update, BotApi $bot) : Void {
        $query = strtoupper($update->getQuery());
        $user = User::find($update->getFrom()->getId());

        $results = [];
        if(preg_match('/^([a-záàâãéèêíïóôõöúçñ]{1,16}) ([0-9])$/i', $query, $matches)) {
            $title = AppString::get('game.confirm_hint');
            $desc = $query;
            $messageContent = new Text(AppString::get('game.hint_sended'));
            $data = CDM::toString([
                CDM::EVENT => CDM::HINT,
                CDM::TEXT => $matches[1],
                CDM::NUMBER => $matches[2]
            ]);
        } else {
            $title = AppString::get('error.wrong_hint_format_title');
            $desc = AppString::get('error.wrong_hint_format_desc');
            $messageContent = new Text($title);
            $data = CDM::toString([
                CDM::EVENT => CDM::IGNORE
            ]);
        }
        
        $results[] = new Article($data, $title, $desc, null, null, null, $messageContent);
        $bot->answerInlineQuery($update->getId(), $results, 10);
    }

}