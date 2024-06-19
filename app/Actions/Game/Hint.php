<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\QueryResult\Article;
use TelegramBot\Api\Types\Inline\InputMessageContent\Text;
use App\Services\CallbackDataManager as CDM;

class Hint implements Action {

    const REGEX_HINT_NUMBER = '/^(?<hint>[A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ]{1,20})( (?<number>[0-9]))?$/';
    const REGEX_HINT_NUMBER_COMPOUND = '/^(?<hint>[A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ -]{1,20})( (?<number>[0-9]))?$/';

    public function run(Update $update, BotApi $bot, string $forceText = null) {
        $query = mb_strtoupper($forceText??$update->getQuery(), 'UTF-8');
        $game = $update->findUser()->currentGame();
        $cardsLeft = $game->cards->where('team', $game->team)->where('revealed', false)->count();

        $results = [];
        if(preg_match($game->chat->compound_words ? self::REGEX_HINT_NUMBER_COMPOUND : self::REGEX_HINT_NUMBER, $query, $matches) && (!isset($matches['number']) || $matches['number'] <= $cardsLeft)) {
            if(!isset($matches['number'])) {
                $matches['number'] = '∞';
            }
            $title = AppString::get('game.confirm_hint');
            $desc = $matches['hint'].' '.$matches['number'];
            $messageContent = new Text(AppString::get('game.hint_sended'));
            $data = CDM::toString([
                CDM::EVENT => CDM::HINT,
                CDM::TEXT => $matches['hint'],
                CDM::NUMBER => $matches['number']
            ]);
        } else {
            $title = AppString::get('error.wrong_hint_format_title');
            $desc = AppString::get('error.wrong_hint_format_desc');
            $messageContent = new Text($title);
            $data = CDM::toString([
                CDM::EVENT => CDM::IGNORE
            ]);
        }

        if($forceText) {
            return $data;
        }
        
        $results[] = new Article($data, $title, $desc, null, null, null, $messageContent);
        $bot->answerInlineQuery($update->getId(), $results, 5, true);
    }

}