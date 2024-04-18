<?php

namespace App\Services\Game\Aux;

class Caption {

    public $title;
    public $text;
    public $titleSize;

    public function __construct(string $title, string $text = null, int $titleSize = 30) {
        $this->title = $title;
        $this->text = $text;
        $this->titleSize = $titleSize;
    }

}