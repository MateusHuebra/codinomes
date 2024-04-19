<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Chat;
use App\Models\User;
use App\Services\AppString;
use App\Services\Game\Menu as MenuService;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Menu implements Action {

    public function run($update, BotApi $bot) : Void {
        $updateId = $update->getId();
        $messageId = $update->getMessage()->getMessageId();
        $chatId = $update->getMessage()->getChat()->getId();
        $userId = $update->getFrom()->getId();
        try {
            $bot->answerCallbackQuery($updateId, AppString::get('settings.loading'));
        } catch(Exception $e) {}
        
        $data = CDM::toArray($update->getData());
        $game = Chat::find($chatId)->game;
        $user = User::find($userId);

        if(!$game || $game->status != 'creating') {
            $bot->deleteMessage($chatId, $messageId);
            return;
        }

        $newMenu = $data[CDM::TEXT];

        if(!$newMenu) {
            $game->menu = null;
        } else {
            $game->menu = $newMenu;
        }

        if($game->getMenu() == 'packs') {
            if(!$user || !$game->hasPermission($user, $bot)) {
                $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.admin_only');
                return;
            }
        }

        $game->save();

        MenuService::send($game, $bot, $messageId, $user);
    }

}