<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class SendList implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('supergroup')) {
            $chat = $update->findChat();
            if (!$chat) {
                return;
            }
            $game = $chat->currentGame();

            $text = $this->getPublicList($game);
        
        } else if($update->isChatType('private')) {
            $user = $update->findUser();
            if (!$user) {
                return;
            }
            $game = $user->currentGame();

            if(!$game) {
                $text = AppString::get('error.no_game');

            } else if($game->player->role == 'master') {
                $text = $this->getPrivateList($game);
                
            } else {
                $text = $this->getPublicList($game);
            }

        } else {
            return;
        }
            
        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, true, true);
    }

    private function getPrivateList(Game $game) {
        $emojis = [
            'w' => Game::COLORS['white'],
            'x' => Game::COLORS['black'],
            'a' => Game::COLORS[$game->getColor('a')],
            'b' => Game::COLORS[$game->getColor('b')]
        ];
        if($game->mode == 'triple') {
            $emojis+= ['c' => Game::COLORS[$game->getColor('c')]];
        }

        $text = '**>';
        $cardsToImplode = [];
        $cards = $game->cards()
                        ->where('revealed', false)
                        ->orderByRaw("CASE WHEN team = '".$game->player->team."' THEN 0 ELSE 1 END")
                        ->orderBy('team')
                        ->orderBy('position')
                        ->get();
        foreach ($cards as $card) {
            $cardsToImplode[] = $emojis[$card->team].' '.AppString::parseMarkdownV2($card->text);
        }
        $text.= implode("\n>", $cardsToImplode).'||';
        return $text;
    }

    private function getPublicList(Game $game) {
        if ($game) {
            $text = '**>';
            $cards = $game->cards()->where('revealed', false)->orderBy('position')->get()->pluck('text')->toArray();
            foreach ($cards as $key => $card) {
                $cards[$key] = AppString::parseMarkdownV2($card);
            }
            $text.= implode("\n>", $cards).'||';
        } else {
            $text = AppString::get('error.no_game');
        }
        return $text;
    }

}