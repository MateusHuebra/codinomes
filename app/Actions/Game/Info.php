<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Info implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('supergroup')) {
            $model = $update->findChat();
        } else if($update->isChatType('private')) {
            $model = $update->findUser();
        } else {
            return;
        }
        
        if (!$model) {
            return;
        }
        $game = $model->currentGame();

        if ($game) {
            $text = AppString::get('mode.'.$game->mode.'_info').PHP_EOL.PHP_EOL.AppString::get('mode.check_other');
        } else {
            $text = AppString::get('error.no_game');
        }
        
        $bot->sendMessage($model->id, $text, null, false, $update->getMessageId(), null, false, null, null, true);
    }

}