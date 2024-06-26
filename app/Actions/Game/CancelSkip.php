<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
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
        if($game->role == 'agent' && $player->role == 'agent' && $player->team == $game->team) {
            $keyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.skip', null, $game->chat->language),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::SKIP
                        ])
                    ],
                    [
                        'text' => AppString::get('game.choose_card', null, $game->chat->language),
                        'switch_inline_query_current_chat' => ''
                    ]
                ]
            ]);
            $bot->editMessageCaption($game->chat_id, $update->getMessageId(), null, $keyboard);
        }
        
    }

}