<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use IntlChar;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\QueryResult\Article;
use TelegramBot\Api\Types\Inline\InputMessageContent\Text;
use App\Services\CallbackDataManager as CDM;

class Hint implements Action {

    const REGEX_HINT_NUMBER = '/^(?<hint>[A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ]{1,20})( (?<number>[0-9]))?$/';
    const REGEX_HINT_EMOJI = '/^(?<hint>.)( (?<number>[0-9]))?$/u';
    const REGEX_HINT_NUMBER_COMPOUND = '/^(?<hint>[A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ -]{1,20})( (?<number>[0-9]))?$/';

    public function run(Update $update, BotApi $bot, string $forceText = null) {
        $query = mb_strtoupper($forceText??$update->getQuery(), 'UTF-8');
        $game = $update->findUser()->currentGame();
        $cardsLeft = $game->mode == 'mystery' ? 9 : $game->cards->where('team', $game->team)->where('revealed', false)->count();

        $results = [];
        if($game->mode == 'emoji') {
            $regex = self::REGEX_HINT_EMOJI;
        } else {
            $regex = $game->chat->compound_words ? self::REGEX_HINT_NUMBER_COMPOUND : self::REGEX_HINT_NUMBER;
        }
        if(
            preg_match($regex, $query, $matches)
            &&
            (!isset($matches['number']) || $matches['number'] <= $cardsLeft)
            &&
            ($game->mode != 'emoji' || $this->isEmoji($matches['hint']))
        ) {
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

    public function isEmoji($char) {
        $codepoint = IntlChar::ord($char);
        return (
            ($codepoint >= 0x1F600 && $codepoint <= 0x1F64F) ||
            ($codepoint >= 0x1F300 && $codepoint <= 0x1F5FF) ||
            ($codepoint >= 0x1F680 && $codepoint <= 0x1F6FF) ||
            ($codepoint >= 0x1F1E0 && $codepoint <= 0x1F1FF) ||
            ($codepoint >= 0x2600 && $codepoint <= 0x26FF) ||
            ($codepoint >= 0x2700 && $codepoint <= 0x27BF) ||
            ($codepoint >= 0x1F900 && $codepoint <= 0x1F9FF) ||
            ($codepoint >= 0x1FA70 && $codepoint <= 0x1FAFF) ||
            ($codepoint >= 0x2B50 && $codepoint <= 0x2B55)
        );
    }

}