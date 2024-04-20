<?php

namespace App\UpdateHandlers;

use App\Adapters\UpdateTypes\Update;

class Factory {

    static function build(Update $update) {

        if($update->isType(Update::MESSAGE)) {
            return new Message;

        } else if($update->isType(Update::CALLBACK_QUERY)) {
            return new CallbackQuery;

        } else if($update->isType(Update::INLINE_QUERY)) {
            return new InlineQuery;

        } else if($update->isType(Update::CHOSEN_INLINE_RESULT)) {
            return new ChosenInlineResult;

        }

    }

}