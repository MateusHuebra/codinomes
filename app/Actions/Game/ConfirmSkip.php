<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;

class ConfirmSkip implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->currentGame();
        $chatLanguage = $game->chat->language;

        if($update->isType(Update::CALLBACK_QUERY)) {
            try {
                $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
            } catch(Exception $e) {}
        } else if($update->isType(Update::MESSAGE)) {
            $bot->tryToDeleteMessage($update->getChatId(), $update->getMessageId());
        }

        if(!$user || !$game) {
            return;
        }
        $player = $game->player;
        if(!($game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team)) {
            return;
        }

        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], $chatLanguage, true);
        $skipped = strtolower(AppString::getParsed('game.skipped', null, $chatLanguage));
        $text = $mention.' '.$skipped;
        
        try {
            $bot->sendMessage($game->chat_id, $text, 'MarkdownV2');
        } catch(Exception $e) {}

        if($game->mode == '8ball' && $game->cards->where('team', $user->getEnemyTeam())->where('revealed', false)->count() == 0) {
            $game->updateStatus('playing', $user->getEnemyTeam(), 'agent');
            $game->setEightBallToHistory($player);

            $title = AppString::get('game.8ball', null, $chatLanguage);

        } else {
            $game->nextStatus($user->getEnemyTeam());

            $title = AppString::get('game.skipped', null, $chatLanguage);
        }

        $caption = new Caption($title, $game->getLastHint());
        Table::send($game, $bot, $caption, null);
    }

}