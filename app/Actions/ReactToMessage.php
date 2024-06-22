<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use TelegramBot\Api\BotApi;

class ReactToMessage implements Action {

    private $emoji;

    public function __construct(string $emoji) {
        $this->emoji = $emoji;
    }

    public function run(Update $update, BotApi $bot) : Void {
        $bot->setMessageReaction($update->getChatId(), $update->getMessageId(), $this->emoji);
    }

}