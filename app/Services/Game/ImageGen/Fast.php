<?php

namespace App\Services\Game\ImageGen;

class Fast Extends Classic {

    public function __construct() {
        $this->fontPath = public_path('open-sans.bold.ttf');
        $this->imageWidth = 860;
        $this->captionSpacing = 3;
        //these can change:
        $this->imageHeight = 1100 - (parent::CARD_HEIGHT*3);
        $this->modeSpacing = 1100 - 250 - 420 + 70;
        $this->firstCardToBePushed = 10;
    }

}