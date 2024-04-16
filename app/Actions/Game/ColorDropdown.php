<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Chat;
use App\Services\AppString;
use App\Services\Game\Menu;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ColorDropdown implements Action {

    public function run($update, BotApi $bot) : Void {
        $updateId = $update->getId();
        $messageId = $update->getMessage()->getMessageId();
        $chatId = $update->getMessage()->getChat()->getId();
        try {
            $bot->answerCallbackQuery($updateId, AppString::get('settings.loading'));
        } catch(Exception $e) {}
        
        $data = CDM::toArray($update->getData());
        $game = Chat::find($chatId)->game;

        if(!$game || $game->status != 'creating') {
            $bot->deleteMessage($chatId, $messageId);
            return;
        }
        
        $game->menu = ($data[CDM::NUMBER] == 1 ? 'color' : null);
        $game->save();

        Menu::send($game, $bot, Menu::EDIT, $messageId);
    }

}