<?php

namespace App\Adapters\UpdateTypes;

use TelegramBot\Api\Types\Update as UpdateType;

class Message extends Update {

    public function __construct(UpdateType $update) {
        parent::__construct($update);

        $this->updateType = self::MESSAGE;
        $this->update = $update->getMessage();
    }

    public function getMessage() {
        if(!isset($this->message)) {
            $this->message = $this->update;
        }
        return $this->message;
    }

    public function getViaBot() {
        if(!isset($this->viaBot)) {
            $this->viaBot = $this->getMessage()->getViaBot();
        }
        return $this->viaBot;
    }

    public function getViaBotId() : int {
        if(!isset($this->viaBotId)) {
            $this->viaBotId = $this->getViaBot()->getId();
        }
        return $this->viaBotId;
    }

    public function getViaBotUsername() : string {
        if(!isset($this->getViaBotUsername)) {
            $this->getViaBotUsername = $this->getViaBot()->getUsername();
        }
        return $this->getViaBotUsername;
    }

}