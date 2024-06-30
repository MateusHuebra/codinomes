<?php

namespace App\Services\Game\ImageGen;
use App\Models\Game;
use App\Services\Game\Aux\CardsLeft;
use App\Services\Game\Aux\Images;

class Copp Extends Classic {

    public function addCardsLeft(Images $images, Game $game, CardsLeft $cardsLeft) {
        if($images->masterImage) {
            $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
        }
        if($images->agentsImage) {
            $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
        }
        
        $squareA = $this->getCardsLeftSquare($game, $cardsLeft->A, 'a', $textColor);
        
        if($images->masterImage) {
            $this->AddCardsLeftToSingleImage($images->masterImage, $squareA);
        }
        if($images->agentsImage) {
            $this->AddCardsLeftToSingleImage($images->agentsImage, $squareA);
        }

        imagedestroy($squareA);
    }

    protected function AddCardsLeftToSingleImage($image, $squareA, $squareB = null, $squareC = null) {
        $x = parent::BORDER;
        imagecopy($image, $squareA, $x, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
    }

}