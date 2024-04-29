<?php

namespace App\Actions;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Help implements Action {

    private $type;

    public function __construct(string $type = 'base') {
        $this->type = $type;
    }

    public function run(Update $update, BotApi $bot) : Void {
        $text = AppString::get('help.'.$this->type);
        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

}