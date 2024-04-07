<?php

namespace App\Services\Telegram;

use TelegramBot\Api\BotApi as OriginalBotApi;

class BotApi extends OriginalBotApi {

    public function setMessageReaction($chatId, $messageId, $emoji = null)
    {
        return $this->call('setMessageReaction', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reaction' => (is_null($emoji)) ? null : json_encode([
                ['type' => 'emoji', 'emoji' => $emoji]
            ]),
        ]);
    }

}