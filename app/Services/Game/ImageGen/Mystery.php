<?php

namespace App\Services\Game\ImageGen;

use App\Models\Game;
use App\Services\Game\Aux\CardsLeft;
use App\Services\Game\Aux\Images;

class Mystery Extends Classic {

    public function addCardsLeft(Images $images, Game $game, CardsLeft $cardsLeft) {
        if($images->masterImage) {
            $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
        }
        if($images->agentsImage) {
            $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
        }
        if($images->masterImage) {
            $squareA = $this->getCardsLeftSquare($game, $cardsLeft->A, 'a', $textColor);
            $squareB = $this->getCardsLeftSquare($game, $cardsLeft->B, 'b', $textColor);
        }
        
        $mysterySquareA = $this->getCardsLeftSquare($game, '?', 'a', $textColor);
        $mysterySquareB = $this->getCardsLeftSquare($game, '?', 'b', $textColor);
        
        if($images->masterImage) {
            $this->AddCardsLeftToSingleImage($images->masterImage, $squareA, $squareB);
        }
        if($images->agentsImage) {
            $this->AddCardsLeftToSingleImage($images->agentsImage, $mysterySquareA ?? $squareA, $mysterySquareB ?? $squareB);
        }

        if($images->masterImage) {
            imagedestroy($squareA);
            imagedestroy($squareB);
        }
    
        imagedestroy($mysterySquareA);
        imagedestroy($mysterySquareB);
    }

}