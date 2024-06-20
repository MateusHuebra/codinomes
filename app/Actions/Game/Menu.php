<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use App\Services\Game\Menu as MenuService;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Menu implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        try {
            $bot->answerCallbackQuery($update->getCallbackQueryId(), AppString::get('settings.loading'));
        } catch(Exception $e) {}
        
        $data = CDM::toArray($update->getData());
        $user = $update->findUser();
        $chat = $update->findChat();
        $game = $chat->currentGame();

        if(!$game) {
            $bot->deleteMessage($chat->id, $update->getMessageId());
            return;
        }

        if(!in_array($game->status, ['creating', 'lobby'])) {
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
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.admin_only');
                return;
            }
        }

        $game->save();

        MenuService::send($game, $bot);
    }

}