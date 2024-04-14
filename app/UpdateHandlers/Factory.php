<?php

namespace App\UpdateHandlers;

use TelegramBot\Api\Types\Update;

class Factory {

    static private $update = null;

    static function build(Update $update) {

        if($update->getMessage()) {
            self::$update = $update;
            return new Message;

        } else if($update->getCallbackQuery()) {
            self::$update = $update->getCallbackQuery();
            return new CallbackQuery;

        } else if($update->getInlineQuery()) {
            self::$update = $update->getInlineQuery();
            return new InlineQuery;

        }

    }

    static function getSpecificUpdateType() {
        return self::$update;
    }

}