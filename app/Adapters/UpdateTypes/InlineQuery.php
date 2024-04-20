<?php

namespace App\Adapters\UpdateTypes;

use TelegramBot\Api\Types\Update as UpdateType;

class InlineQuery extends Update {

    public function __construct(UpdateType $update) {
        parent::__construct($update);

        $this->updateType = self::INLINE_QUERY;
        $this->update = $update->getInlineQuery();
    }

}