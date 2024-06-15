<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\GameCard;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use TelegramBot\Api\BotApi;
use CURLFile;
use Exception;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class Table {
    
    const BORDER = 10;
    const FONT_SIZE = 21;
    const EASTER_EGGS = ['MANOEL GOMES','THE WEEKND']; // might add later: 'LADY GAGA','AMOR','MEDO','SANGUE','MORTE','ENERGIA','CONHECIMENTO','KIAN'
    static $fontPath;
    static $imageWidth;
    static $imageHeight;
    static $captionSpacing;
    static $modeSpacing;
    static $firstCardToBePushed;

    static function send(Game $game, BotApi $bot, Caption $caption, int $highlightCard = null, string $winner = null, bool $sendToBothMasters = false) {
        self::setVarsByGameMode($game->mode);
        self::$fontPath = public_path('open-sans.bold.ttf');
        $chatLanguage = $game->chat->language;
        $backgroundColor = ($winner) ? $game->{'color_'.$winner} : $game->{'color_'.$game->team};

        if(in_array($game->mode, ['crazy, sp_crazy'])) {
            $cards = $game->cards()->get();
        } else {
            $cards = $game->cards;
        }
        $leftA = $cards->where('team', 'a')->where('revealed', false)->count();
        $leftB = $cards->where('team', 'b')->where('revealed', false)->count();

        if(($leftA==1 && $leftB==7) || ($leftA==7 && $leftB==1)) {
            UserAchievement::add($game->users, 'seven_one', $bot, $game->chat_id);
        }
        
        $sendToMasters = ($sendToBothMasters || $game->role == 'master' || $winner);

        $backgroundImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        if($sendToMasters) {
            $masterImage = imagecreatetruecolor(self::$imageWidth, self::$imageHeight);
            imagecopy($masterImage, $backgroundImage, 0, 0, 0, 0, self::$imageWidth, self::$imageHeight);
        }
        if(!$winner) {
            $agentsImage = imagecreatetruecolor(self::$imageWidth, self::$imageHeight);
            imagecopy($agentsImage, $backgroundImage, 0, 0, 0, 0, self::$imageWidth, self::$imageHeight);
        }
        imagedestroy($backgroundImage);

        self::addCardsLeft($masterImage??null, $agentsImage??null, $game, $leftA, $leftB);
        foreach($cards as $card) {
            self::addCard($masterImage??null, $agentsImage??null, $card, $game, $highlightCard);
        }
        self::addCaption($masterImage??null, $agentsImage??null, $caption);
        self::addMode($masterImage??null, $agentsImage??null, $game->mode);

        if($sendToMasters) {
            $tempMasterImageFileName = tempnam(sys_get_temp_dir(), 'm_image_');
            $masterPhoto = self::getCURLFileFromImage($masterImage, $tempMasterImageFileName, 'master');
        }

        if(!is_null($game->message_id)) {
            $bot->tryToDeleteMessage($game->chat_id, $game->message_id);
        }

        if(!$winner) {
            $tempAgentsImageFileName = tempnam(sys_get_temp_dir(), 'a_image_');
            $agentsPhoto = self::getCURLFileFromImage($agentsImage, $tempAgentsImageFileName, 'agents');

            $keyboard = self::getKeyboard($game, $chatLanguage);
            $text = $game->getPhotoCaption();
            if($game->history !== null) {
                $text.= PHP_EOL.$game->getHistory($game->mode == 'mystery');
            }
            
            $message = $bot->sendPhoto($game->chat_id, $agentsPhoto, $text, null, $keyboard, false, 'MarkdownV2');
            unlink($tempAgentsImageFileName);

            if($sendToMasters) {
                $text = AppString::getParsed('game.send_hint');
                if($game->history !== null) {
                    $text.= PHP_EOL.$game->getHistory();
                }
                try{
                    if($sendToBothMasters || $game->team == 'a') {
                        $bot->sendPhoto($game->users()->fromTeamRole('a', 'master')->first()->id, $masterPhoto, ($game->team=='a')?$text:null, null, null, false, 'MarkdownV2', null, true);
                    }
                    if($sendToBothMasters || $game->team == 'b') {
                        $bot->sendPhoto($game->users()->fromTeamRole('b', 'master')->first()->id, $masterPhoto, ($game->team=='b')?$text:null, null, null, false, 'MarkdownV2', null, true);
                    }
                    unlink($tempMasterImageFileName);
                } catch(Exception $e) {
                    $bot->sendMessage($game->chat_id, AppString::get('error.master_not_registered', null, $chatLanguage));
                }
            }

            $game->message_id = $message->getMessageId();
            $game->save();
            
        } else {
            $color = ($winner == 'a') ? $game->color_a : $game->color_b;
            $team = AppString::get('color.'.$color).' '.Game::COLORS[$color];

            $text = Menu::getLobbyText($game);
            $text.= AppString::getParsed('game.win', [
                'team' => $team
            ], $chatLanguage);
            $text.= PHP_EOL.$game->getHistory();

            $message = $bot->sendPhoto($game->chat_id, $masterPhoto, $text, null, null, false, 'MarkdownV2');
            $title = '\\'.$game->chat_id."\n".AppString::parseMarkdownV2($game->chat->title)."\n\n";
            $message = $bot->sendPhoto(env('TG_MY_ID'), $masterPhoto, $title.$text, null, null, false, 'MarkdownV2');
            unlink($tempMasterImageFileName);

            $game->stop($bot, $winner);

            UserAchievement::testEndGame($game->users, $bot, $game->chat_id);
        }
    }

    private static function setVarsByGameMode(string $gameMode) {
        self::$imageWidth = 860;
        switch ($gameMode) {
            case 'fast':
                self::$imageHeight = 680;
                self::$captionSpacing = 1000 - 420;
                self::$modeSpacing = 960 - 420;
                self::$firstCardToBePushed = 10;
                break;
            
            default:
                self::$imageHeight = 1100;
                self::$captionSpacing = 1000;
                self::$modeSpacing = 960;
                self::$firstCardToBePushed = 22;
                break;
        }
    }

    private static function getCURLFileFromImage($image, $tempFileName, string $fileName) : CURLFile {
        imagepng($image, $tempFileName);
        imagedestroy($image);
        return new CURLFile($tempFileName,'image/png',$fileName);
    }

    private static function getKeyboard(Game $game, string $chatLanguage) {
        if($game->role=='master') {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.open_dm', null, $chatLanguage),
                        'url' => 't.me/CodinomesBot'
                    ]
                ]
            ]);
        } else {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.skip', null, $chatLanguage),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::SKIP
                        ])
                    ],
                    [
                        'text' => AppString::get('game.choose_card', null, $chatLanguage),
                        'switch_inline_query_current_chat' => ''
                    ]
                ]
            ]);
        }
    }
    
    private static function getMasterKeyboard(string $chatLanguage) {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('game.give_hint', null, $chatLanguage),
                    'switch_inline_query_current_chat' => ''
                ]
            ]
        ]);
    }

    private static function addCaption($masterImage, $agentsImage, Caption $caption) {
        $title = $caption->title;
        $axis = self::getAxisToCenterText($caption->titleSize, $title, 860, 82);
        $axis['y']+= + self::$captionSpacing;

        if(!is_null($caption->text)) {
            $text = $caption->text;
            $textSize = floor($caption->titleSize*0.8);
            $axisText = self::getAxisToCenterText($textSize, $text, 860, 90);
            $axisText['y'] = $axis['y'] + ($textSize/2) + 5;
            $axis['y'] = $axis['y'] - ($textSize/2) - 5;
            if($masterImage) {
                $textColor = imagecolorallocate($masterImage, 255, 255, 255);
                imagefttext($masterImage, $textSize, 0, $axisText['x'], $axisText['y'], $textColor, self::$fontPath, $text);
            }
            if($agentsImage) {
                $textColor = imagecolorallocate($agentsImage, 255, 255, 255);
                imagefttext($agentsImage, $textSize, 0, $axisText['x'], $axisText['y'], $textColor, self::$fontPath, $text);
            }
        }
        
        if($masterImage) {
            $textColor = imagecolorallocate($masterImage, 255, 255, 255);
            imagefttext($masterImage, $caption->titleSize, 0, $axis['x'], $axis['y'], $textColor, self::$fontPath, $title);
        }
        if($agentsImage) {
            $textColor = imagecolorallocate($agentsImage, 255, 255, 255);
            imagefttext($agentsImage, $caption->titleSize, 0, $axis['x'], $axis['y'], $textColor, self::$fontPath, $title);
        }
    }

    private static function addMode($masterImage, $agentsImage, string $gameMode) {
        if($gameMode == 'classic') {
            return;
        }
        
        try {
            $modeImage = imagecreatefrompng(public_path("images/{$gameMode}_mode.png"));

            if($masterImage) {
                imagecopy($masterImage, $modeImage, 0, self::$modeSpacing, 0, 0,860, 140);
            }
            if($agentsImage) {
                imagecopy($agentsImage, $modeImage, 0, self::$modeSpacing, 0, 0,860, 140);
            }
            imagedestroy($modeImage);
        } catch(Exception $e) {
            return;
        }
    }

    private static function addCardsLeft($masterImage, $agentsImage, Game $game, int $leftA, int $leftB) {
        $fontSize = 65;
        if($masterImage) {
            $textColor = imagecolorallocate($masterImage, 255, 255, 255);
        }
        if($agentsImage) {
            $textColor = imagecolorallocate($agentsImage, 255, 255, 255);
        }
        if($masterImage || $game->mode != 'mystery') {
            $squareA = imagecreatefrompng(public_path("images/{$game->color_a}_square.png"));
            $squareB = imagecreatefrompng(public_path("images/{$game->color_b}_square.png"));
            $axisA = self::getAxisToCenterText($fontSize, $leftA, 210, 140);
            $axisB = self::getAxisToCenterText($fontSize, $leftB, 210, 140);
            imagefttext($squareA, $fontSize, 0, $axisA['x'], $axisA['y'], $textColor, self::$fontPath, $leftA);
            imagefttext($squareB, $fontSize, 0, $axisB['x'], $axisB['y'], $textColor, self::$fontPath, $leftB);
        }
        if($game->mode == 'mystery') {
            $mysterySquareA = imagecreatefrompng(public_path("images/{$game->color_a}_square.png"));
            $mysterySquareB = imagecreatefrompng(public_path("images/{$game->color_b}_square.png"));
            $mysteryLeft = '?';
            $axisA = self::getAxisToCenterText($fontSize, $mysteryLeft, 210, 140);
            $axisB = self::getAxisToCenterText($fontSize, $mysteryLeft, 210, 140);
            imagefttext($mysterySquareA, $fontSize, 0, $axisA['x'], $axisA['y'], $textColor, self::$fontPath, $mysteryLeft);
            imagefttext($mysterySquareB, $fontSize, 0, $axisB['x'], $axisB['y'], $textColor, self::$fontPath, $mysteryLeft);
        }
        $y = self::BORDER;
        if($masterImage) {
            $x = self::BORDER;
            imagecopy($masterImage, $squareA, $x, $y, 0, 0, 210, 140);
            $x = self::BORDER+(3*210);
            imagecopy($masterImage, $squareB, $x, $y, 0, 0, 210, 140);
        }
        if($agentsImage) {
            $x = self::BORDER;
            imagecopy($agentsImage, $mysterySquareA ?? $squareA, $x, $y, 0, 0, 210, 140);
            $x = self::BORDER+(3*210);
            imagecopy($agentsImage, $mysterySquareB ?? $squareB, $x, $y, 0, 0, 210, 140);
        }
        if($masterImage || $game->mode != 'mystery') {
            imagedestroy($squareA);
            imagedestroy($squareB);
        }
        if($game->mode == 'mystery') {
            imagedestroy($mysterySquareA);
            imagedestroy($mysterySquareB);
        }
    }

    private static function getAxisToCenterText($fontSize, $text, $width, $height) {
        $noAccentText = str_replace(['Á', 'À', 'Â', 'Ã'], 'A', $text);
        $noAccentText = str_replace(['É', 'È', 'Ê'], 'E', $noAccentText);
        $noAccentText = str_replace(['Í', 'Ï', 'J'], 'I', $noAccentText);
        $noAccentText = str_replace(['Ó', 'Ô', 'Õ', 'Ö', 'Q'], 'O', $noAccentText);
        $noAccentText = str_replace(['Ú', 'Ç', 'Ñ'], ['U', 'C', 'N'], $noAccentText);

        $textBox = imagettfbbox($fontSize, 0, self::$fontPath, $noAccentText);
        $textWidth = $textBox[2] - $textBox[0];
        $textHeight = $textBox[1] - $textBox[7];
        $result['width'] = $textWidth;
        $result['height'] = $textHeight;
        $result['x'] = ($width - $textWidth) / 2;
        $result['y'] = ($height + $textHeight) / 2;

        return $result;
    }

    private static function addCard($masterImage, $agentsImage, GameCard $card, Game $game, int $highlightCard = null) {
        #region calculations
        //card position
        $cardByLine = 4;
        if($card->position<2) {
            $y = 0;
            $x = $card->position + 1;
        } else {
            $y = floor(($card->position+2) / $cardByLine);
            $x = $card->position+2 - ($cardByLine*$y);
        }
        
        $cardX = self::BORDER+($x*210);
        $cardY = self::BORDER+($y*140);
        if($card->position >= self::$firstCardToBePushed) {
            $cardX+= 105;
        }

        //text position and size
        $fontSize = self::FONT_SIZE + 1;
        $inputWidth = 192;
        $inputHeight = 34;
        $textAxis = [];
        while(empty($textAxis) || $textAxis['width'] >= $inputWidth - 30) {
            $fontSize-=1;
            $textAxis = self::getAxisToCenterText($fontSize, $card->text, 210, $inputHeight);
        }
        $bottomSpace = 23;
        $textAxis['y'] = 140-$bottomSpace-$inputHeight+$textAxis['y'];
        #endregion

        switch ($card->team) {
            case 'a':
                $colorMaster = $game->color_a;
                break;
            case 'b':
                $colorMaster = $game->color_b;
                break;
            case 'x':
                $colorMaster = 'black';
                break;
            
            default:
                $colorMaster = 'white';
                break;
        }

        if($card->revealed || !is_null($masterImage)) {
            $masterCardImage = imagecreatefrompng(public_path("images/{$colorMaster}_card.png"));
            if($colorMaster=='black') {
                $textColor = imagecolorallocate($masterCardImage, 255, 255, 255);
            } else {
                $textColor = imagecolorallocate($masterCardImage, 0, 0, 0);
            }

            if($card->revealed) {       
                if($game->mode == 'mystery') {
                    $agentsCardImage = imagecreatefrompng(public_path("images/white_card.png"));
                    imagefttext($agentsCardImage, $fontSize, 0, $textAxis['x'], $textAxis['y'], $textColor, self::$fontPath, $card->text);
                    self::markCardAsRevealed($agentsCardImage);
                    if(false === self::highlightCardIfNeeded($agentsCardImage, $card, $highlightCard)) {
                        $textColor = imagecolorallocate($agentsCardImage, 150, 150, 150);
                    }
                }

                if(in_array($card->text, self::EASTER_EGGS)) {
                    $easterEggImage = imagecreatefrompng(public_path("images/eggs/{$card->text}.png"));
                    imagecopy($masterCardImage, $easterEggImage, 0, 0, 0, 0, 210, 140);
                } else {
                    self::markCardAsRevealed($masterCardImage);
                }

                if(false === self::highlightCardIfNeeded($masterCardImage, $card, $highlightCard)) {
                    $textColor = imagecolorallocate($masterCardImage, 150, 150, 150);
                }
            }

            imagefttext($masterCardImage, $fontSize, 0, $textAxis['x'], $textAxis['y'], $textColor, self::$fontPath, $card->text);
        }

        if(!$card->revealed) {
            $agentsCardImage = imagecreatefrompng(public_path("images/white_card.png"));
            $textColor = imagecolorallocate($agentsCardImage, 0, 0, 0);
            imagefttext($agentsCardImage, $fontSize, 0, $textAxis['x'], $textAxis['y'], $textColor, self::$fontPath, $card->text);
        }
        
        if(!is_null($masterImage)) {
            imagecopy($masterImage, $masterCardImage, $cardX, $cardY, 0, 0, 210, 140);
            imagedestroy($masterCardImage);
        }
        if(!is_null($agentsImage)) {
            imagecopy($agentsImage, $agentsCardImage??$masterCardImage, $cardX, $cardY, 0, 0, 210, 140);
            if(isset($agentsCardImage)) {
                imagedestroy($agentsCardImage);
            }
        }

    }

    private static function markCardAsRevealed($image) {
        $revealedImage = imagecreatefrompng(public_path("images/revealed_card.png"));
        imagecopy($image, $revealedImage, 0, 0, 0, 0, 210, 140);
        imagedestroy($revealedImage);
    }

    private static function highlightCardIfNeeded($image, GameCard $card, int $highlightCard = null) {
        if($card->position === $highlightCard) {
            $highlightedImage = imagecreatefrompng(public_path("images/highlighted_card.png"));
            imagecopy($image, $highlightedImage, 0, 0, 0, 0, 210, 140);
            imagedestroy($highlightedImage);
            return true;
        }
        return false;
    }

}