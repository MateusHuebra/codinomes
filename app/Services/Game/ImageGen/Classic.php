<?php

namespace App\Services\Game\ImageGen;

use App\Models\Game;
use App\Models\GameCard;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\CardsLeft;
use App\Services\Game\Aux\Images;
use Illuminate\Database\Eloquent\Collection;

class Classic {

    const EASTER_EGGS = ['MANOEL GOMES','THE WEEKND']; // might add later: 'LADY GAGA','AMOR','MEDO','SANGUE','MORTE','ENERGIA','CONHECIMENTO','KIAN'
    protected $fontPath;
    protected $imageWidth;
    protected $imageHeight;
    protected $captionSpacing;
    protected $modeSpacing;
    protected $firstCardToBePushed;
    
    const CARDS_BY_LINE = 4;
    const BORDER = 10;
    const FONT_SIZE = 21;
    const CARD_HEIGHT = 140;
    const CARD_WIDTH = 210;
    const CARD_INPUT_WIDTH = 192;
    const CARD_INPUT_HEIGHT = 34;
    const CARD_BOTTOM_SPACE = 23;

    public function __construct() {
        $this->fontPath = public_path('open-sans.bold.ttf');
        $this->imageWidth = 860;
        $this->captionSpacing = 3;
        //these can change:
        $this->imageHeight = 1100;
        $this->modeSpacing = self::CARD_HEIGHT*7;
        $this->firstCardToBePushed = 22;
    }

    public function getBaseImages(Game $game, bool $sendToMasters, string $winner = null) {
        $backgroundColor = ($winner) ? $game->getColor($winner) : $game->getColor($game->team);
        $images = new Images;
        $backgroundImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        if($sendToMasters) {
            $images->masterImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
            imagecopy($images->masterImage, $backgroundImage, 0, 0, 0, 0, $this->imageWidth, $this->imageHeight);
        }
        if(!$winner) {
            $images->agentsImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
            imagecopy($images->agentsImage, $backgroundImage, 0, 0, 0, 0, $this->imageWidth, $this->imageHeight);
        }
        imagedestroy($backgroundImage);
        return $images;
    }

    public function addCards(Images $images, Collection $cards, Game $game, int $highlightCard = null) {
        foreach($cards as $card) {
            $this->addCard($images, $card, $game, $highlightCard);
        }
    }

    protected function addCard(Images $images, GameCard $card, Game $game, int $highlightCard = null) {
        $cardAxis = $this->getCardPositonAxis($card->position);
        $textAxis = $this->getCardTextAxis($card->text);
        $colorMaster = $this->getColorMaster($card->team, $game);

        if($card->revealed || !is_null($images->masterImage)) {
            $masterCardImage = imagecreatefrompng(public_path("images/{$colorMaster}_card.png"));
            $rgbTextColor = $colorMaster=='black' ? 255 : 0;
            
            if($card->revealed) {       
                if($game->mode == Game::MYSTERY) {
                    $agentsCardImage = imagecreatefrompng(public_path("images/white_card.png"));
                    $this->markCardAsRevealed($agentsCardImage);
                    if(false === $this->highlightCardIfNeeded($agentsCardImage, $card, $highlightCard)) {
                        $rgbTextColor = 150;
                    }
                    $textColor = imagecolorallocate($agentsCardImage, $rgbTextColor, $rgbTextColor, $rgbTextColor);
                    imagefttext($agentsCardImage, $textAxis['size'], 0, $textAxis['x'], $textAxis['y'], $textColor, $this->fontPath, $card->text);
                    
                }

                $this->markCardAsRevealedConsideringEasterEgg($masterCardImage, $card->text);
                if(false === $this->highlightCardIfNeeded($masterCardImage, $card, $highlightCard)) {
                    $rgbTextColor = 150;
                }
            }

            $textColor = imagecolorallocate($masterCardImage, $rgbTextColor, $rgbTextColor, $rgbTextColor);
            imagefttext($masterCardImage, $textAxis['size'], 0, $textAxis['x'], $textAxis['y'], $textColor, $this->fontPath, $card->text);
        }
        if(!$card->revealed) {
            $agentsCardImage = imagecreatefrompng(public_path("images/white_card.png"));
            $textColor = imagecolorallocate($agentsCardImage, 0, 0, 0);
            imagefttext($agentsCardImage, $textAxis['size'], 0, $textAxis['x'], $textAxis['y'], $textColor, $this->fontPath, $card->text);
        }
        
        if(!is_null($images->masterImage)) {
            imagecopy($images->masterImage, $masterCardImage, $cardAxis['x'], $cardAxis['y'], 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
            imagedestroy($masterCardImage);
        }
        if(!is_null($images->agentsImage)) {
            imagecopy($images->agentsImage, $agentsCardImage??$masterCardImage, $cardAxis['x'], $cardAxis['y'], 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
            if(isset($agentsCardImage)) {
                imagedestroy($agentsCardImage);
            }
        }

    }

    protected function getColorMaster(string $team, Game $game) {
        switch ($team) {
            case 'a':
                return $game->getColor('a');
            case 'b':
                return $game->getColor('b');
            case 'c':
                return $game->getColor('c');
            case 'x':
                return 'black';
            default:
                return 'white';        
        }
    }

    protected function getCardPositonAxis(int $position) {
        $y = floor(($position) / self::CARDS_BY_LINE);
        $x = $position - (self::CARDS_BY_LINE*$y);
        $cardX = self::BORDER+($x*self::CARD_WIDTH);
        $cardY = ($y*self::CARD_HEIGHT)+self::CARD_HEIGHT;
        return [
            'x' => $cardX,
            'y' => $cardY
        ];
    }

    protected function getCardTextAxis(string $text) {
        $fontSize = self::FONT_SIZE;
        $textAxis = [];
        while(empty($textAxis) || $textAxis['width'] >= self::CARD_INPUT_WIDTH - 30) {
            $textAxis = $this->getAxisToCenterText($fontSize, $text, self::CARD_WIDTH, self::CARD_INPUT_HEIGHT);
            $fontSize-=1;
        }
        $textAxis['y'] = self::CARD_HEIGHT - self::CARD_BOTTOM_SPACE - self::CARD_INPUT_HEIGHT + $textAxis['y'];
        $textAxis['size'] = $fontSize + 1;
        return $textAxis;
    }

    public function addMode(Images $images, string $gameMode) {
        $filePath = public_path("images/{$gameMode}_mode.png");

        if(file_exists($filePath)) {
            $modeImage = imagecreatefrompng($filePath);
            $x = self::BORDER+(1.5*self::CARD_WIDTH);

            if($images->masterImage) {
                imagecopy($images->masterImage, $modeImage, $x, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
            }
            if($images->agentsImage) {
                imagecopy($images->agentsImage, $modeImage, $x, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
            }
            imagedestroy($modeImage);
        }
    }

    public function addCaption(Images $images, Caption $caption) {
        if($caption->isEmoji) {
            $caption->isEmoji = false;
            $subject = !is_null($caption->text) ? 'text' : 'title';
            if(preg_match('/^(?<hint>\S+)( +(?<number>[0-9∞]))?$/u', $caption->{$subject}, $matches)) {
                $emoji = \Emoji\is_single_emoji($matches['hint']);
                if($emoji) {
                    $emoji = $emoji['hex_str'];
                    $emojiPath = public_path("images/openmoji-72x72-color/{$emoji}.png");
                    if(file_exists($emojiPath)) {
                        $caption->{$subject} = '       '.$matches['number'];
                        $caption->isEmoji = true;
                    } else {
                        $caption->{$subject} = $matches['number'];
                    }
                }
            }          
        }
        $title = mb_strtoupper($caption->title, 'UTF-8');
        $axisTitle = self::getAxisToCenterText($caption->titleSize, $title, 860, 120);
        $axisTitle['y'] = $this->imageHeight-$this->captionSpacing-120+$axisTitle['y'];

        if(!is_null($caption->text)) {
            $text = $caption->text;
            $textSize = floor($caption->titleSize*0.8);
            $axisText = self::getAxisToCenterText($textSize, $text, 860, 120);
            $axisText['y'] = $axisTitle['y'] + ($textSize/2) + 10;
            $axisTitle['y'] = $axisTitle['y'] - ($textSize/2);
            if($images->masterImage) {
                $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
                imagefttext($images->masterImage, $textSize, 0, $axisText['x'], $axisText['y'], $textColor, $this->fontPath, $text);
            }
            if($images->agentsImage) {
                $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
                imagefttext($images->agentsImage, $textSize, 0, $axisText['x'], $axisText['y'], $textColor, $this->fontPath, $text);
            }
        }
        if($caption->isEmoji) {
            $emojiImage = imagecreatefrompng($emojiPath);
            if($subject == 'title') {
                $axisEmoji = [
                    'x' => 360,
                    'y' => $this->imageHeight - 113,
                    'size' => 100
                ];
            } else {
                $axisEmoji = [
                    'x' => 380,
                    'y' => $this->imageHeight - 65,
                    'size' => 60
                ];
            }
            if($images->masterImage) {
                $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
                imagecopyresampled($images->masterImage, $emojiImage, $axisEmoji['x'], $axisEmoji['y'], 0, 0, $axisEmoji['size'], $axisEmoji['size'], 72, 72);
                
            }
            if($images->agentsImage) {
                $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
                imagecopyresampled($images->agentsImage, $emojiImage, $axisEmoji['x'], $axisEmoji['y'], 0, 0, $axisEmoji['size'], $axisEmoji['size'], 72, 72);
            }
        }
        
        if($images->masterImage) {
            $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
            imagefttext($images->masterImage, $caption->titleSize, 0, $axisTitle['x'], $axisTitle['y'], $textColor, $this->fontPath, $title);
        }
        if($images->agentsImage) {
            $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
            imagefttext($images->agentsImage, $caption->titleSize, 0, $axisTitle['x'], $axisTitle['y'], $textColor, $this->fontPath, $title);
        }
    }

    public function addCardsLeft(Images $images, Game $game, CardsLeft $cardsLeft) {
        if($images->masterImage) {
            $textColor = imagecolorallocate($images->masterImage, 255, 255, 255);
        }
        if($images->agentsImage) {
            $textColor = imagecolorallocate($images->agentsImage, 255, 255, 255);
        }

        $squareA = $this->getCardsLeftSquare($game, $cardsLeft->A, 'a', $textColor);
        $squareB = $this->getCardsLeftSquare($game, $cardsLeft->B, 'b', $textColor);
        
        if($images->masterImage) {
            $this->AddCardsLeftToSingleImage($images->masterImage, $squareA, $squareB);
        }
        if($images->agentsImage) {
            $this->AddCardsLeftToSingleImage($images->agentsImage, $squareA, $squareB);
        }

        imagedestroy($squareA);
        imagedestroy($squareB);
    }

    protected function getCardsLeftSquare(Game $game, $cardsLeft, string $team, $textColor) {
        $square = imagecreatefrompng(public_path('images/'.$game->getColor($team).'_square.png'));
        $axisA = self::getAxisToCenterText(65, $cardsLeft, self::CARD_WIDTH, self::CARD_HEIGHT);
        imagefttext($square, 65, 0, $axisA['x'], $axisA['y'], $textColor, $this->fontPath, $cardsLeft);
        return $square;
    }

    protected function AddCardsLeftToSingleImage($image, $squareA, $squareB = null, $squareC = null) {
        $x = self::BORDER;
        imagecopy($image, $squareA, $x, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
        $x = self::BORDER+(3*self::CARD_WIDTH);
        imagecopy($image, $squareB, $x, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
    }

    protected function getAxisToCenterText($fontSize, $text, $width, $height) {
        $noAccentText = str_replace(['Á', 'À', 'Â', 'Ã'], 'A', $text);
        $noAccentText = str_replace(['É', 'È', 'Ê'], 'E', $noAccentText);
        $noAccentText = str_replace(['Í', 'Ï', 'J'], 'I', $noAccentText);
        $noAccentText = str_replace(['Ó', 'Ô', 'Õ', 'Ö', 'Q'], 'O', $noAccentText);
        $noAccentText = str_replace(['Ú', 'Ç', 'Ñ'], ['U', 'C', 'N'], $noAccentText);

        $textBox = imagettfbbox($fontSize, 0, $this->fontPath, $noAccentText);
        $textWidth = $textBox[2] - $textBox[0];
        $textHeight = $textBox[1] - $textBox[7];
        $result['width'] = $textWidth;
        $result['height'] = $textHeight;
        $result['x'] = ($width - $textWidth) / 2;
        $result['y'] = ($height + $textHeight) / 2;

        return $result;
    }

    protected function markCardAsRevealedConsideringEasterEgg($image, string $text) {
        if(in_array($text, self::EASTER_EGGS)) {
            $easterEggImage = imagecreatefrompng(public_path("images/eggs/{$text}.png"));
            imagecopy($image, $easterEggImage, 0, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
        } else {
            $this->markCardAsRevealed($image);
        }
    }

    protected function markCardAsRevealed($image) {
        $revealedImage = imagecreatefrompng(public_path("images/revealed_card.png"));
        imagecopy($image, $revealedImage, 0, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
        imagedestroy($revealedImage);
    }

    protected function highlightCardIfNeeded($image, GameCard $card, int $highlightCard = null) {
        if($card->position === $highlightCard) {
            $highlightedImage = imagecreatefrompng(public_path("images/highlighted_card.png"));
            imagecopy($image, $highlightedImage, 0, 0, 0, 0, self::CARD_WIDTH, self::CARD_HEIGHT);
            imagedestroy($highlightedImage);
            return true;
        }
        return false;
    }

}