<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class Skip implements Action {

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
            $text = AppString::get('game.sure_skip');
            $keyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.cancel', null, $game->chat->language),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::CANCEL_SKIP
                        ])
                    ]
                ],
                [
                    [
                        'text' => AppString::get('game.confirm', null, $game->chat->language),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::CONFIRM_SKIP
                        ])
                    ]
                ]
            ]);
            $bot->editMessageCaption($game->chat_id, $update->getMessageId(), $text, $keyboard);
        }
        
    }

}