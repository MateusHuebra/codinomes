<?php

namespace App\Services\Game\Aux;

class GuessData {

    public $title;
    public $text;
    public $winner;
    public $attemptType;

    public function __construct(string $title = null, string $attemptType = null, string $text = null, string $winner = null) {
        $this->title = $title;
        $this->text = $text;
        $this->winner = $winner;
        $this->attemptType = $attemptType;
    }

}