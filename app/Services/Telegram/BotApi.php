<?php

namespace App\Services\Telegram;

use App\Services\AppString;
use Exception;
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

    public function sendAlertOrMessage(int $callbackQueryId, int $chatId, string $stringPath) {
        $string = AppString::get($stringPath);
        try {
            $this->answerCallbackQuery($callbackQueryId, $string, true);
        } catch(Exception $e) {
            $this->sendMessage($chatId, $string);
        }
    }

    public function tryToDeleteMessage($chatId, $messageId) {
        try {
            return $this->deleteMessage($chatId, $messageId);
        } catch(Exception $e) {}
    }

    public function tryToPinChatMessage($chatId, $messageId, $disableNotification = false) {
        try {
            return $this->pinChatMessage($chatId, $messageId, $disableNotification);
        } catch(Exception $e) {}
    }

    public function sendPhoto($chatId, $photo, $caption = null, $replyToMessageId = null, $replyMarkup = null, $disableNotification = false, $parseMode = null, $messageThreadId = null, $protectContent = null, $allowSendingWithoutReply = null) {
        try {
            return parent::sendPhoto($chatId, $photo, $caption, $replyToMessageId, $replyMarkup, $disableNotification, $parseMode, $messageThreadId, $protectContent, $allowSendingWithoutReply);
        } catch(Exception $e) {
            parent::sendMessage(env('TG_MY_ID'), "error trying to send photo to $chatId");
            return parent::sendPhoto($chatId, $photo, $caption, $replyToMessageId, $replyMarkup, $disableNotification, $parseMode, $messageThreadId, $protectContent, $allowSendingWithoutReply);
        }
    }

}