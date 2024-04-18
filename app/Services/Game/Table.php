<?php

namespace App\Services\Game;

use App\Models\Chat;
use App\Models\Game;
use App\Models\GameCard;
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
    static $fontPath;

    static function send(Game $game, BotApi $bot, Caption $caption, int $highlightCard = null, string $winner = null, bool $sendToBothMasters = false) {
        self::$fontPath = public_path('open-sans.bold.ttf');
        $chatId = $game->chat_id;
        $chatLanguage = Chat::find($chatId)->language;
        $oldMessage = $game->message_id;
        if($winner) {
            $backgroundColor = $winner == 'a' ? $game->color_a : $game->color_b;
        } else {
            $backgroundColor = ($game->status=='master_a' || $game->status=='agent_a') ? $game->color_a : $game->color_b;
        }
        $cards = $game->cards;
        $leftA = $cards->where('team', 'a')->where('revealed', false)->count();
        $leftB = $cards->where('team', 'b')->where('revealed', false)->count();
        
        $sendToMasters = ($sendToBothMasters || $game->status == 'master_a' || $game->status == 'master_b' || $winner);

        if($sendToMasters) {
            $masterImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        }
        if(!$winner) {
            $agentsImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        }

        self::addCardsLeft($masterImage??null, $agentsImage??null, $game, $leftA, $leftB);

        foreach($cards as $card) {
            self::addCard($masterImage??null, $agentsImage??null, $card, $game, $highlightCard);
        }

        self::addCaption($masterImage??null, $agentsImage??null, $caption);

        if($sendToMasters) {
            $tempMasterImageFileName = tempnam(sys_get_temp_dir(), 'm_image_');
            imagepng($masterImage, $tempMasterImageFileName);
            imagedestroy($masterImage);
            $masterPhoto = new CURLFile($tempMasterImageFileName,'image/png','master');
        }
        if(!$winner) {
            $tempAgentsImageFileName = tempnam(sys_get_temp_dir(), 'a_image_');
            imagepng($agentsImage, $tempAgentsImageFileName);
            imagedestroy($agentsImage);
            $agentsPhoto = new CURLFile($tempAgentsImageFileName,'image/png','agents');
        }

        if(!$winner) {
            $keyboard = self::getKeyboard($game->status, $chatLanguage);
            switch ($game->status) {
                case 'master_a':
                    $role = AppString::get('game.master', null, $chatLanguage);
                    $teamColor = 'color_a';
                    $playersList = $game->users()->fromTeamRole('a', 'master')->get()->toMentionList(PHP_EOL);
                    break;
                case 'agent_a':
                    $role = AppString::get('game.agents', null, $chatLanguage);
                    $teamColor = 'color_a';
                    $playersList = $game->users()->fromTeamRole('a', 'agent')->get()->toMentionList(PHP_EOL);
                    break;
                case 'master_b':
                    $role = AppString::get('game.master', null, $chatLanguage);
                    $teamColor = 'color_b';
                    $playersList = $game->users()->fromTeamRole('b', 'master')->get()->toMentionList(PHP_EOL);
                    break;
                case 'agent_b':
                    $role = AppString::get('game.agents', null, $chatLanguage);
                    $teamColor = 'color_b';
                    $playersList = $game->users()->fromTeamRole('b', 'agent')->get()->toMentionList(PHP_EOL);
                    break;
            }
            
            $text = AppString::get('game.turn', [
                'role' => $role,
                'team' =>  Game::COLORS[$game->$teamColor],
                'players' => $playersList
            ], $chatLanguage);

            $message = $bot->sendPhoto($chatId, $agentsPhoto, $text, null, $keyboard, false, 'MarkdownV2');
            unlink($tempAgentsImageFileName);

            if($sendToMasters) {
                try{
                    if($sendToBothMasters || $game->status == 'master_a') {
                        $bot->sendPhoto($game->users()->fromTeamRole('a', 'master')->first()->id, $masterPhoto, null, null, ($game->status=='master_a')?self::getMasterKeyboard($chatLanguage):null, false, 'MarkdownV2', null, true);
                    }
                    if($sendToBothMasters || $game->status == 'master_b') {
                        $bot->sendPhoto($game->users()->fromTeamRole('b', 'master')->first()->id, $masterPhoto, null, null, ($game->status=='master_b')?self::getMasterKeyboard($chatLanguage):null, false, 'MarkdownV2', null, true);
                    }
                    unlink($tempMasterImageFileName);
                } catch(Exception $e) {
                    $bot->sendMessage($chatId, AppString::get('error.master_not_registered', null, $chatLanguage));
                }
            }

            $game->message_id = $message->getMessageId();
            $game->save();
            
        } else {
            $color = ($winner == 'a') ? $game->color_a : $game->color_b;
            $team = AppString::get('color.'.$color).' '.Game::COLORS[$color];
            $text = AppString::getParsed('game.win', [
                'team' => $team
            ], $chatLanguage);

            $message = $bot->sendPhoto($chatId, $masterPhoto, $text, null, null, false, 'MarkdownV2');
            unlink($tempMasterImageFileName);

            $game->stop();
        }

        try {
            $bot->pinChatMessage($chatId, $message->getMessageId(), true);
            if(!is_null($oldMessage)) {
                $bot->deleteMessage($chatId, $oldMessage);
            }
        } catch(Exception $e) {}
    }

    static function getKeyboard(string $status, string $chatLanguage) {
        if($status=='master_a' || $status=='master_b') {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.give_hint', null, $chatLanguage),
                        'url' => 't.me/CodinomesBot'
                    ]
                ]
            ]);
        } else {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.choose_card', null, $chatLanguage),
                        'switch_inline_query_current_chat' => ''
                    ]
                ],
                [
                    [
                        'text' => AppString::get('game.skip', null, $chatLanguage),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::SKIP
                        ])
                    ]
                ]
            ]);
        }
    }
    
    static function getMasterKeyboard(string $chatLanguage) {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('game.give_hint', null, $chatLanguage),
                    'switch_inline_query_current_chat' => ''
                ]
            ]
        ]);
    }

    static function addCaption($masterImage, $agentsImage, Caption $caption) {
        $title = $caption->title;
        $axis = self::getAxisToCenterText($caption->titleSize, $title, 860, 82);
        $axis['y']+= + 1000;

        if(!is_null($caption->text)) {
            $text = $caption->text;
            $textSize = floor($caption->titleSize*0.7);
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

    static function addCardsLeft($masterImage, $agentsImage, Game $game, int $leftA, int $leftB) {
        if($masterImage) {
            $textColor = imagecolorallocate($masterImage, 255, 255, 255);
        }
        if($agentsImage) {
            $textColor = imagecolorallocate($agentsImage, 255, 255, 255);
        }
        $fontSize = 65; 
        $squareA = imagecreatefrompng(public_path("images/{$game->color_a}_square.png"));
        $squareB = imagecreatefrompng(public_path("images/{$game->color_b}_square.png"));
        $axisA = self::getAxisToCenterText($fontSize, $leftA, 210, 140);
        $axisB = self::getAxisToCenterText($fontSize, $leftB, 210, 140);
        imagefttext($squareA, $fontSize, 0, $axisA['x'], $axisA['y'], $textColor, self::$fontPath, $leftA);
        imagefttext($squareB, $fontSize, 0, $axisB['x'], $axisB['y'], $textColor, self::$fontPath, $leftB);
        $y = self::BORDER;
        if($masterImage) {
            $x = self::BORDER;
            imagecopy($masterImage, $squareA, $x, $y, 0, 0, 210, 140);
            $x = self::BORDER+(3*210);
            imagecopy($masterImage, $squareB, $x, $y, 0, 0, 210, 140);
        }
        if($agentsImage) {
            $x = self::BORDER;
            imagecopy($agentsImage, $squareA, $x, $y, 0, 0, 210, 140);
            $x = self::BORDER+(3*210);
            imagecopy($agentsImage, $squareB, $x, $y, 0, 0, 210, 140);
        }
    }

    static function getAxisToCenterText($fontSize, $text, $width, $height) {
        $textBox = imagettfbbox($fontSize, 0, self::$fontPath, $text);
        $textWidth = $textBox[2] - $textBox[0];
        $textHeight = $textBox[1] - $textBox[7];
        $result['x'] = ($width - $textWidth) / 2;
        $result['y'] = ($height + $textHeight) / 2;
        return $result;
    }

    static function addCard($masterImage, $agentsImage, GameCard $card, Game $game, int $highlightCard = null) {
        #region calculations
        //card position
        $cardByLine = 4;
        if($card->id<2) {
            $y = 0;
            $x = $card->id + 1;
        } else {
            $y = floor(($card->id+2) / $cardByLine);
            $x = $card->id+2 - ($cardByLine*$y);
        }
        
        $cardX = self::BORDER+($x*210);
        $cardY = self::BORDER+($y*140);
        if($card->id > 21) {
            $cardX+= 105;
        }

        //text position
        $textLen = strlen($card->text);
        if($textLen<9) {
            $fontSize = self::FONT_SIZE;
            $bottomSpace = 5;
        } else if($textLen<11) {
            $fontSize = self::FONT_SIZE-2;
            $bottomSpace = 5 + 1;
        } else {
            $fontSize = self::FONT_SIZE-6;
            $bottomSpace = 5 + 3;
        }
        $textAxis = self::getAxisToCenterText($fontSize, $card->text, 210, 140);
        $textAxis['y'] = 115 - $bottomSpace;
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

        if(!is_null($masterImage) || $card->revealed) {       
            $masterCardImage = imagecreatefrompng(public_path("images/{$colorMaster}_card.png"));
            if($colorMaster=='black') {
                $textColor = imagecolorallocate($masterCardImage, 255, 255, 255);
            } else {
                $textColor = imagecolorallocate($masterCardImage, 0, 0, 0);
            }

            if($card->revealed) {
                $revealedImage = imagecreatefrompng(public_path("images/revealed_card.png"));
                imagecopy($masterCardImage, $revealedImage, 0, 0, 0, 0, 210, 140);
                if($card->id === $highlightCard) {
                    $highlightedImage = imagecreatefrompng(public_path("images/highlighted_card.png"));
                    imagecopy($masterCardImage, $highlightedImage, 0, 0, 0, 0, 210, 140);
                } else {
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
        }
        if(!is_null($agentsImage)) {
            imagecopy($agentsImage, $agentsCardImage??$masterCardImage, $cardX, $cardY, 0, 0, 210, 140);
        }
        
    }

}