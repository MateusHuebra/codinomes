<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class CancelSkip implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->currentGame();

        try {
            $bot->answerCallbackQuery($update->getId(), AppString::get('settings.loading'));
        } catch(Exception $e) {}

        if(!$user || !$game) {
            return;
        }

        $player = $game->player;
        if(
            ($game->mode != Game::COOP && $game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team)
            || ($game->mode == Game::COOP && $game->role == $player->role)
        ) {
            $keyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.skip', null, ($game->chat??$game->creator)->language),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::SKIP
                        ])
                    ],
                    [
                        'text' => AppString::get('game.choose_card', null, ($game->chat??$game->creator)->language),
                        'switch_inline_query_current_chat' => ''
                    ]
                ]
            ]);
            $bot->editMessageCaption($update->getChatId(), $update->getMessageId(), null, $keyboard);
        }
        
    }

}