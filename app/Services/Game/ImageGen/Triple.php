<?php

namespace App\Services\Game\ImageGen;

use App\Models\Game;
use App\Services\Game\Aux\Images;
use App\Services\Game\Aux\CardsLeft;

class Triple Extends Classic {

    public function addMode(Images $images, string $gameMode) {
        return;
    }

    public function addCardsLeft(Images $images, Game $game, CardsLeft $cardsLeft) {
        if($images->masterImage) {
            $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
        }
        if($images->agentsImage) {
            $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
        }

        $squareA = $this->getCardsLeftSquare($game, $cardsLeft->A, $textColor, 'a');
        $squareB = $this->getCardsLeftSquare($game, $cardsLeft->B, $textColor, 'b');
        $squareC = $this->getCardsLeftSquare($game, $cardsLeft->C, $textColor, 'c');
        
        if($images->masterImage) {
            $this->AddCardsLeftToSingleImage($images->masterImage, $squareA, $squareB, $squareC);
        }
        if($images->agentsImage) {
            $this->AddCardsLeftToSingleImage($images->agentsImage, $squareA, $squareB, $squareC);
        }
        
        imagedestroy($squareA);
        imagedestroy($squareB);
        imagedestroy($squareC);
    }

    protected function AddCardsLeftToSingleImage($image, $squareA, $squareB = null, $squareC = null) {
        $x = parent::BORDER;
        imagecopy($image, $squareA, $x, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
        $x = parent::BORDER+(1.5*parent::CARD_WIDTH);
        imagecopy($image, $squareB, $x, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
        $x = parent::BORDER+(3*parent::CARD_WIDTH);
        imagecopy($image, $squareC, $x, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
    }

}