<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameTeamColor;
use App\Models\TeamColor;
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
            'w' => GameTeamColor::COLORS['white'],
            'x' => GameTeamColor::COLORS['black'],
            'a' => TeamColor::where('shortname', $game->getColor('a'))->first()->emoji,
        ];
        if($game->mode != Game::COOP) {
            $emojis+= ['b' => TeamColor::where('emoji', $game->getColor('b'))->first()->emoji];
        }
        if($game->mode == Game::TRIPLE) {
            $emojis+= ['c' => TeamColor::where('emoji', $game->getColor('c'))->first()->emoji];
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
                    $title = $card->text;
                    $messageContent = new Text($title);
                    $results[] = new Article($game->id.$card->position, $title, null, null, null, null, $messageContent);
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

        $player = $game->player;
        $revealedField = $player->role == 'master' ? 'coop_revealed' : 'revealed';
        $otherTeamField = $player->role == 'agent' ? 'coop_team' : 'team';
        $otherRevealedField = $player->role == 'agent' ? 'coop_revealed' : 'revealed';

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