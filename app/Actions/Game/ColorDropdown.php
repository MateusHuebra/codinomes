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
        
        $chat = Chat::find($chatId);
        $data = CDM::toArray($update->getData());
        $changeColor = ($data[CDM::NUMBER]);

        Menu::send($chat->game, $bot, Menu::EDIT, $messageId, $changeColor);
    }

}