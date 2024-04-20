<?php

namespace App\Adapters\UpdateTypes;

use TelegramBot\Api\Types\Update as UpdateType;

class Factory {

    static function build(UpdateType $update) {

        if($update->getMessage()) {
            return new Message($update);

        } else if($update->getCallbackQuery()) {
            return new CallbackQuery($update);

        } else if($update->getInlineQuery()) {
            return new InlineQuery($update);

        } else if($update->getChosenInlineResult()) {
            return new ChosenInlineResult($update);

        }

    }

}