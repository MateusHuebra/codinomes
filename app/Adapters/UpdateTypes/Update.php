<?php

namespace App\Adapters\UpdateTypes;

use TelegramBot\Api\Types\Chat;
use TelegramBot\Api\Types\Update as UpdateType;
use TelegramBot\Api\Types\User;

abstract class Update {

    const MESSAGE = 'message';
    const CALLBACK_QUERY = 'callback_query';
    const INLINE_QUERY = 'inline_query';
    const CHOSEN_INLINE_RESULT = 'chosen_inline_result';

    public $baseUpdate;
    protected $updateType;
    protected $update;

    #region parent methods
    public function __construct(UpdateType $update) {
        $this->baseUpdate = $update;
    }

    public function __call($name, $arguments) {
        return $this->update->$name($arguments);
    }

    public function getType() : String {
        return $this->updateType;
    }

    public function isType(string $type) : Bool {
        return $this->updateType === $type;
    }

    public function getUpdateId() : int {
        if(!isset($this->updateId)) {
            $this->updateId = $this->baseUpdate->getUpdateId();
        }
        return $this->updateId;
    }
    #endregion

    #generic methods
    public function getFrom() : User {
        if(!isset($this->from)) {
            $this->from = $this->update->getFrom();
        }
        return $this->from;
    }

    public function getFromId() : int {
        if(!isset($this->fromId)) {
            $this->fromId = $this->getFrom()->getId();
        }
        return $this->fromId;
    }

    public function getFromUsername() {
        if(!isset($this->fromUsername)) {
            $this->fromUsername = $this->getFrom()->getUsername();
        }
        return $this->fromUsername;
    }
    
    public function getMessage() {
        if(!isset($this->message)) {
            $this->message = $this->update->getMessage();
        }
        return $this->message;
    }

    public function getChat() : Chat {
        if(!isset($this->chat)) {
            $this->chat = $this->getMessage()->getChat();
        }
        return $this->chat;
    }

    public function getChatId() : int {
        if(!isset($this->chatId)) {
            $this->chatId = $this->getChat()->getId();
        }
        return $this->chatId;
    }

    public function getChatTitle() {
        if(!isset($this->chatTitle)) {
            $this->chatTitle = $this->getChat()->getTitle();
        }
        return $this->chatTitle;
    }

    public function getChatUsername() {
        if(!isset($this->chatUsername)) {
            $this->chatUsername = $this->getChat()->getUsername();
        }
        return $this->chatUsername;
    }

    public function getChatType() {
        if(!isset($this->chatType)) {
            $this->chatType = $this->getChat()->getType();
        }
        return $this->chatType;
    }

    public function isChatType(string $type) {
        return $this->getChatType() === $type;
    }
    #endregion
    public function getReplyToMessage() {
        if(!isset($this->replyToMessage)) {
            $this->replyToMessage = $this->getMessage()->getReplyToMessage();
        }
        return $this->replyToMessage;
    }

    public function getReplyToMessageFrom() {
        if(!isset($this->replyToMessageFrom)) {
            $this->replyToMessageFrom = $this->getReplyToMessage()->getFrom();
        }
        return $this->replyToMessageFrom;
    }

    public function getReplyToMessageFromId() {
        if(!isset($this->replyToMessageFromId)) {
            $this->replyToMessageFromId = $this->getReplyToMessageFrom()->getId();
        }
        return $this->replyToMessageFromId;
    }
    #region reply to methods

    #endregion

    #region methods which need child methods
    public function getMessageId() {
        if(!isset($this->messageId)) {
            $this->messageId = $this->getMessage()->getMessageId();
        }
        return $this->messageId;
    }

    public function getMessageText() {
        if(!isset($this->messageText)) {
            $this->messageText = $this->getMessage()->getText();
        }
        return $this->messageText;
    }
    #endregion

    #region model methods
    /**
     * @return \App\Models\User|null
     */
    public function findUser() {
        if(!isset($this->userModel)) {
            $this->userModel = \App\Models\User::find($this->getFromId());
        }
        return $this->userModel;
    }

    /**
     * @return \App\Models\Chat|null
     */
    public function findChat() {
        if(!isset($this->chatModel)) {
            $this->chatModel = \App\Models\Chat::find($this->getChatId());
        }
        return $this->chatModel;
    }
    #endregion

}