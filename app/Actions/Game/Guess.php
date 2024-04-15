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

class Guess implements Action {

    public function run($update, BotApi $bot) : Void {
        $user = User::find($update->getFrom()->getId());
        $game = Game::find($user->game_id);

        if(!(($game->status=='agent_a' && $user->team=='a' && $user->role=='agent') || ($game->status=='agent_b' && $user->team=='b' && $user->role=='agent'))) {
            return;
        }

        $query = mb_strtoupper($update->getQuery(), 'UTF-8');
        $cards = $game->cards;

        $results = [];
        if(preg_match('/^([a-záàâãéèêíïóôõöúçñ]{1,12})$/i', $query, $matches)) {
            $filteredCards = $cards->where('revealed', false);
            $filteredCards = $filteredCards->filter(function ($card) use ($query) {
                return mb_strpos($card->text, $query, 0, 'UTF-8') === 0;
            });
            foreach($filteredCards as $card) {
                $title = $card->text;
                $messageContent = new Text($title);
                $data = CDM::toString([
                    CDM::EVENT => CDM::GUESS,
                    CDM::NUMBER => $card->id
                ]);
                $results[] = new Article($data, $title, null, 'https://imgur.com/3kQB4ed', null, null, $messageContent);
            }
            
        } else {
            $title = AppString::get('error.wrong_guess_format_title');
            $desc = AppString::get('error.wrong_guess_format_desc');
            $messageContent = new Text($title);
            $data = CDM::toString([
                CDM::EVENT => CDM::IGNORE
            ]);
            $results[] = new Article($data, $title, $desc, null, null, null, $messageContent);
        }

        $bot->answerInlineQuery($update->getId(), $results, 10);
    }

}