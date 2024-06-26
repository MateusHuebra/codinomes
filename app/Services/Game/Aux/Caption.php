<?php

namespace App\Services\Game\Aux;

class Caption {

    public $title;
    public $text;
    public $titleSize;
    public $isEmoji;

    public function __construct(string $title, string $text = null, int $titleSize = 30, bool $isEmoji = false) {
        $this->title = $title;
        $this->text = $text;
        $this->titleSize = $titleSize;
        $this->isEmoji = $isEmoji;
    }

}