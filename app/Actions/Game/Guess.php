<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\QueryResult\Article;
use TelegramBot\Api\Types\Inline\InputMessageContent\Text;

class Guess implements Action {

    const REGEX = '/^([ A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.]{1,16})?$/';

    public function run(Update $update, BotApi $bot) : Void {
        $game = $update->findUser()->game;
        $emojis = [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->color_a],
            'b' => Game::COLORS[$game->color_b]
        ];

        $query = mb_strtoupper($update->getQuery(), 'UTF-8');
        $cards = $game->cards;

        $results = [];
        if(preg_match(self::REGEX, $query, $matches)) {
            $filteredCards = $cards->where('revealed', false);
            if(strlen($query)) {
                $filteredCards = $filteredCards->filter(function ($card) use ($query) {
                    return mb_strpos($card->text, $query, 0, 'UTF-8') === 0;
                });
            }
            if($filteredCards->count() == 0) {
                $results[] = $this->getErrorResult();
            } else {
                foreach($filteredCards as $card) {
                    $title = $card->text;
                    $messageContent = new Text($emojis[$card->team].' '.$title);
                    $data = CDM::toString([
                        CDM::EVENT => CDM::GUESS,
                        CDM::NUMBER => $card->position
                    ]);
                    $results[] = new Article($data, $title, null, null, null, null, $messageContent);
                }
            }
            
        } else {
            $results[] = $this->getErrorResult();
        }

        $bot->answerInlineQuery($update->getId(), $results, 5, true);
    }

    private function getErrorResult() {
        $title = AppString::get('error.wrong_guess_format_title');
        $desc = AppString::get('error.wrong_guess_format_desc');
        $messageContent = new Text($title);
        $data = CDM::toString([
            CDM::EVENT => CDM::IGNORE
        ]);
        return new Article($data, $title, $desc, null, null, null, $messageContent);
    }

}