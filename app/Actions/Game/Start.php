<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameCard;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use TelegramBot\Api\BotApi;
use Exception;

class Start implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        $game = $chat->game;

        if(!$game) {
            $bot->deleteMessage($chat->id, $update->getMessageId());
            return;
        }

        $user = $update->findUser();
        $game->start($bot, $user, $update->getMessageId());
    }

}