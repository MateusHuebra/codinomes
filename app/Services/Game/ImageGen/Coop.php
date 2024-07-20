<?php

namespace App\Services\Game\ImageGen;
use App\Models\Game;
use App\Models\GameCard;
use App\Services\Game\Aux\CardsLeft;
use App\Services\Game\Aux\Images;
use App\Services\ServerLog;

class Coop Extends Classic {

    public function getBaseImages(Game $game, bool $sendToMasters, string $winner = null) {
        $backgroundColor = $game->getColor('a');
        $images = new Images;
        $backgroundImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));

        $images->masterImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        imagecopy($images->masterImage, $backgroundImage, 0, 0, 0, 0, $this->imageWidth, $this->imageHeight);
        $images->agentsImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        imagecopy($images->agentsImage, $backgroundImage, 0, 0, 0, 0, $this->imageWidth, $this->imageHeight);
        imagedestroy($backgroundImage);

        return $images;
    }

    protected function addCard(Images $images, GameCard $card, Game $game, int $highlightCard = null) {
        $cardAxis = $this->getCardPositonAxis($card->position);
        $textAxis = $this->getCardTextAxis($card->text);
        $colorMaster = $this->getColorMaster($card->team, $game);
        $colorPartner = $this->getColorMaster($card->coop_team, $game);

        if(($card->revealed && $card->team!='w') || ($card->coop_revealed && $card->coop_team!='w')) {
            $color = ($card->revealed && !$card->coop_revealed) || ($card->revealed && $card->coop_revealed && $card->team != 'w')
                    ? $colorMaster
                    : $colorPartner;
            $bothCardImage = imagecreatefrompng(public_path("images/{$color}_card.png"));
            $rgbTextColor = $color=='black' ? 255 : 0;

            $this->markCardAsRevealedConsideringEasterEgg($bothCardImage, $card->text);
            if(false === $this->highlightCardIfNeeded($bothCardImage, $card, $highlightCard)) {
                $rgbTextColor = 150;
            }

            $textColor = imagecolorallocate($bothCardImage, $rgbTextColor, $rgbTextColor, $rgbTextColor);
            imagefttext($bothCardImage, $textAxis['size'], 0, $textAxis['x'], $textAxis['y'], $textColor, $this->fontPath, $card->text);

            if($card->team == 'a' && $card->coop_team == 'a') {
                $doubleImage = imagecreatefrompng(public_path("images/coop_double_card.png"));
                if($card->revealed) {
                    $masterCardImage = imagecreatetruecolor(parent::CARD_WIDTH, parent::CARD_HEIGHT);
                    imagealphablending($masterCardImage, false);
                    imagesavealpha($masterCardImage, true);
                    imagecopy($masterCardImage, $bothCardImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                    imagecopy($bothCardImage, $doubleImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                    $agentsCardImage = $bothCardImage;
                } else {
                    $agentsCardImage = imagecreatetruecolor(parent::CARD_WIDTH, parent::CARD_HEIGHT);
                    imagealphablending($agentsCardImage, false);
                    imagesavealpha($agentsCardImage, true);
                    imagecopy($agentsCardImage, $bothCardImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                    imagecopy($bothCardImage, $doubleImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                    $masterCardImage = $bothCardImage;
                }
                $bothCardImage = null;
                imagedestroy($doubleImage);
            
            } else if($card->revealed && $card->team == 'a' && $card->coop_team == 'x') {
                $blackImage = imagecreatefrompng(public_path("images/coop_black_card.png"));
                $masterCardImage = imagecreatetruecolor(parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagealphablending($masterCardImage, false);
                imagesavealpha($masterCardImage, true);
                imagecopy($masterCardImage, $bothCardImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagecopy($bothCardImage, $blackImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                $agentsCardImage = $bothCardImage;
                $bothCardImage = null;
                imagedestroy($blackImage);
            
            } else if($card->coop_revealed && $card->team == 'x' && $card->coop_team == 'a') {
                $blackImage = imagecreatefrompng(public_path("images/coop_black_card.png"));
                $agentsCardImage = imagecreatetruecolor(parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagealphablending($agentsCardImage, false);
                imagesavealpha($agentsCardImage, true);
                imagecopy($agentsCardImage, $bothCardImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagecopy($bothCardImage, $blackImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                $masterCardImage = $bothCardImage;
                $bothCardImage = null;
                imagedestroy($blackImage);
            }
        
        } else {
            $masterCardImage = imagecreatefrompng(public_path("images/{$colorMaster}_card.png"));
            $rgbTextColor = $colorMaster=='black' ? 255 : 0;
            $masterTextColor = imagecolorallocate($masterCardImage, $rgbTextColor, $rgbTextColor, $rgbTextColor);
            imagefttext($masterCardImage, $textAxis['size'], 0, $textAxis['x'], $textAxis['y'], $masterTextColor, $this->fontPath, $card->text);
            
            $agentsCardImage = imagecreatefrompng(public_path("images/{$colorPartner}_card.png"));
            $rgbTextColor = $colorPartner=='black' ? 255 : 0;
            $agentsTextColor = imagecolorallocate($agentsCardImage, $rgbTextColor, $rgbTextColor, $rgbTextColor);
            imagefttext($agentsCardImage, $textAxis['size'], 0, $textAxis['x'], $textAxis['y'], $agentsTextColor, $this->fontPath, $card->text);
        
            if($card->revealed) {
                $revealedImage = imagecreatefrompng(public_path("images/coop_white_right.png"));
                imagecopy($masterCardImage, $revealedImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagecopy($agentsCardImage, $revealedImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagedestroy($revealedImage);
            }
            if($card->coop_revealed) {
                $revealedImage = imagecreatefrompng(public_path("images/coop_white_left.png"));
                imagecopy($masterCardImage, $revealedImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagecopy($agentsCardImage, $revealedImage, 0, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
                imagedestroy($revealedImage);
            }
        }
        
        imagecopy($images->masterImage, $bothCardImage??$masterCardImage, $cardAxis['x'], $cardAxis['y'], 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
        imagecopy($images->agentsImage, $bothCardImage??$agentsCardImage, $cardAxis['x'], $cardAxis['y'], 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);

        if(isset($bothCardImage)) {
            imagedestroy($bothCardImage);
        }
        if(isset($masterCardImage)) {
            imagedestroy($masterCardImage);
        }
        if(isset($agentsCardImage)) {
            imagedestroy($agentsCardImage);
        }

    }

    public function addCardsLeft(Images $images, Game $game, CardsLeft $cardsLeft) {
        if($images->masterImage) {
            $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
        }
        if($images->agentsImage) {
            $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
        }

        $squareA = $this->getCardsLeftSquare($game, $cardsLeft->A, $textColor, 'a');
        $squareB = $this->getCardsLeftSquare($game, $cardsLeft->attemptsLeft, $textColor);
        
        if($images->masterImage) {
            $this->AddCardsLeftToSingleImage($images->masterImage, $squareA, $squareB);
        }
        if($images->agentsImage) {
            $this->AddCardsLeftToSingleImage($images->agentsImage, $squareA, $squareB);
        }

        imagedestroy($squareA);
        imagedestroy($squareB);
    }

    protected function AddCardsLeftToSingleImage($image, $squareA, $squareB = null, $squareC = null) {
        $x = parent::BORDER;
        imagecopy($image, $squareA, $x, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
        $x = parent::BORDER+(3*parent::CARD_WIDTH);
        imagecopy($image, $squareB, $x, 0, 0, 0, parent::CARD_WIDTH, parent::CARD_HEIGHT);
    }

}