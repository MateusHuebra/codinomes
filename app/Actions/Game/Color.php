<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Color implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user->currentGame()) {
            $bot->answerCallbackQuery($update->getCallbackQueryId());
            return;
        }

        $game = $user->currentGame();
        $player = $game->player;
        if(!($game->status=='creating' && $player->role=='master')) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $game->chat_id, 'error.master_only');
            return;
        }

        $data = CDM::toArray($update->getData());
        $newColor = $data[CDM::TEXT];

        if($newColor == $game->getColor($user->getEnemyTeam())) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $game->chat_id, 'error.color_taken');
            return;
        }
        
        $game->setColor($player->team, $newColor);
        $game->menu = null;
        $game->save();
        
        Menu::send($game, $bot);

        if($user->default_color == null && rand(1, 10) == 1) {
            $bot->sendMessage($update->getChatId(), AppString::get('color.suggestion'));
        }
    }

}