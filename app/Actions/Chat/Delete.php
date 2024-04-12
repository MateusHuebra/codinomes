<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Models\Chat;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Types\Message;

class Delete implements Action {

    public function run($update, BotApi $bot) : Void {
        $chat = Chat::find($update->getMessage()->getChat()->getId());
        $chat->delete();
    }

}