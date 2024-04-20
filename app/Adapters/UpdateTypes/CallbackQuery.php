<?php

namespace App\Adapters\UpdateTypes;

use TelegramBot\Api\Types\Chat;
use TelegramBot\Api\Types\Update as UpdateType;

class CallbackQuery extends Update {

    public function __construct(UpdateType $update) {
        parent::__construct($update);

        $this->updateType = self::CALLBACK_QUERY;
        $this->update = $update->getCallbackQuery();
    }

    public function getCallbackQueryId() {
        if(!isset($this->callbackQueryId)) {
            $this->callbackQueryId = $this->update->getId();
        }
        return $this->callbackQueryId;
    }

}