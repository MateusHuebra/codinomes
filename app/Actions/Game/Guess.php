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
        $game = $update->findUser()->currentGame();
        $emojis = [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')]
        ];
        if($game->mode != Game::COOP) {
            $emojis+= ['b' => Game::COLORS[$game->getColor('b')]];
        }
        if($game->mode == Game::TRIPLE) {
            $emojis+= ['c' => Game::COLORS[$game->getColor('c')]];
        }

        $query = mb_strtoupper($update->getQuery(), 'UTF-8');
        $cards = $this->getCards($game);

        $results = [];
        if(preg_match(self::REGEX, $query, $matches)) {
            if(strlen($query)) {
                $cards = $cards->filter(function ($card) use ($query) {
                    return mb_strpos($card->text, $query, 0, 'UTF-8') === 0;
                });
            }
            if($cards->count() == 0) {
                $results[] = $this->getErrorResult();
            } else {
                foreach($cards as $card) {
                    $emoji = ($game->mode == Game::MYSTERY) ? '❔' : $emojis[$game->role == 'agent' ? $card->team : $card->coop_team];
                    $title = $card->text;
                    $messageContent = new Text($emoji.' '.$title);
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

    private function getCards(Game $game) {
        if($game->mode != Game::COOP) {
            return $game->cards()
                        ->where('revealed', false)
                        ->orderBy('position')
                        ->get();
        }

        $revealedField = $game->role == 'master' ? 'coop_revealed' : 'revealed';
        $otherTeamField = $game->role == 'agent' ? 'coop_team' : 'team';
        $otherRevealedField = $game->role == 'agent' ? 'coop_revealed' : 'revealed';

        return $game->cards()
                ->where($revealedField, false)
                ->where(function($query) use ($otherTeamField, $otherRevealedField) {
                    $query->where($otherRevealedField, false)
                          ->orWhere(function($query) use ($otherTeamField, $otherRevealedField) {
                              $query->where($otherRevealedField, true)
                                    ->where($otherTeamField, 'w');
                          });
                })
                ->orderBy('position')
                ->get();
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