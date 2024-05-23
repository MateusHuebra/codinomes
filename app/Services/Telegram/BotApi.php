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

    public function tryToSetMessageReaction($chatId, $messageId, $emoji = null) {
        try {
            return $this->setMessageReaction($chatId, $messageId, $emoji);
        } catch(Exception $e) {}
    }

    public function sendAlertOrMessage($callbackQueryId, int $chatId, string $stringPath) {
        $string = AppString::get($stringPath);
        try {
            if($callbackQueryId === 1) {
                throw new Exception;
            }
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

    public function tryToUnpinChatMessage($chatId, $messageId) {
        try {
            return $this->unpinChatMessage($chatId, $messageId);
        } catch(Exception $e) {}
    }

    public function sendPhoto($chatId, $photo, $caption = null, $replyToMessageId = null, $replyMarkup = null, $disableNotification = false, $parseMode = null, $messageThreadId = null, $protectContent = null, $allowSendingWithoutReply = null) {
        try {
            return parent::sendPhoto($chatId, $photo, $caption, $replyToMessageId, $replyMarkup, $disableNotification, $parseMode, $messageThreadId, $protectContent, $allowSendingWithoutReply);
        } catch(Exception $e) {
            return parent::sendPhoto($chatId, $photo, $caption, $replyToMessageId, $replyMarkup, $disableNotification, $parseMode, $messageThreadId, $protectContent, $allowSendingWithoutReply);
        }
    }

    public function sendMessage($chatId, $text, $parseMode = null, $disablePreview = false, $replyToMessageId = null, $replyMarkup = null, $disableNotification = false, $messageThreadId = null, $protectContent = null, $allowSendingWithoutReply = null) {
        try {
            return parent::sendMessage($chatId, $text, $parseMode, $disablePreview, $replyToMessageId, $replyMarkup, $disableNotification, $messageThreadId, $protectContent, $allowSendingWithoutReply);
        } catch(Exception $e) {
            return parent::sendMessage($chatId, $text, $parseMode, $disablePreview, $replyToMessageId, $replyMarkup, $disableNotification, $messageThreadId, $protectContent, $allowSendingWithoutReply);
        }
    }

}