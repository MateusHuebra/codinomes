<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class SendList implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
            return;
        }
        $chat = $update->findChat();
        if (!$chat) {
            return;
        }
        $game = $chat->currentGame();

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
        
        $bot->sendMessage($chat->id, $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

}