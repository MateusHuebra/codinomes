<?php

namespace App\Adapters\UpdateTypes;

use TelegramBot\Api\Types\Update as UpdateType;

class ChosenInlineResult extends Update {

    public function __construct(UpdateType $update) {
        parent::__construct($update);

        $this->updateType = self::CHOSEN_INLINE_RESULT;
        $this->update = $update->getChosenInlineResult();
    }

}