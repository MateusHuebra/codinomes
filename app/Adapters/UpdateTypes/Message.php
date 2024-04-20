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

}