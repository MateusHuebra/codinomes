<?php

namespace App\Services\Game;

use App\Models\Chat;
use App\Models\Game;
use App\Models\GameCard;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use CURLFile;
use Exception;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class Table {
    
    static $border = 15;
    static $fontSize = 23;

    static function send(Game $game, BotApi $bot, string $hint = null, bool $sendToMasters = true, string $winner = null) {
        $chatId = $game->chat_id;
        $chatLanguage = Chat::find($chatId)->language;
        $backgroundColor = ($game->status=='master_a' || $game->status=='agent_a' ? 'purple' : 'orange');
        if($sendToMasters) {
            $masterImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        }
        if(!$winner) {
            $agentsImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        }

        $cards = $game->cards;
        foreach($cards as $card) {
            self::addCard($masterImage??null, $agentsImage??null, $card, true);
        }

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
                    $team = Game::TEAM['a']['emoji'];
                    $playersList = $game->users()->fromTeamRole('a', 'master')->get()->toMentionList();
                    break;
                case 'agent_a':
                    $role = AppString::get('game.agents', null, $chatLanguage);
                    $team = Game::TEAM['a']['emoji'];
                    $playersList = $game->users()->fromTeamRole('a', 'agent')->get()->toMentionList();
                    break;
                case 'master_b':
                    $role = AppString::get('game.master', null, $chatLanguage);
                    $team = Game::TEAM['b']['emoji'];
                    $playersList = $game->users()->fromTeamRole('b', 'master')->get()->toMentionList();
                    break;
                case 'agent_b':
                    $role = AppString::get('game.agents', null, $chatLanguage);
                    $team = Game::TEAM['b']['emoji'];
                    $playersList = $game->users()->fromTeamRole('b', 'agent')->get()->toMentionList();
                    break;
            }
    
            $leftA = $cards->where('team', 'a')->where('revealed', false)->count();
            $leftB = $cards->where('team', 'b')->where('revealed', false)->count();
            
            $text = AppString::get('game.turn', [
                'role' => $role,
                'team' =>  $team,
                'players' => $playersList,
                'team_a' => Game::TEAM['a']['emoji'],
                'team_b' => Game::TEAM['b']['emoji'],
                'left_a' => $leftA,
                'left_b' => $leftB
            ], $chatLanguage);
            if($hint) {
                $text.= "\n\n".AppString::get('game.hint', [
                    'hint' => $hint
                ], $chatLanguage);
            }

            $bot->sendPhoto($chatId, $agentsPhoto, $text, null, $keyboard, false, 'MarkdownV2');
            unlink($tempAgentsImageFileName);
            if($sendToMasters) {
                try{
                    $bot->sendPhoto($game->users()->fromTeamRole('a', 'master')->first()->id, $masterPhoto, null, null, ($game->status=='master_a')?self::getMasterKeyboard($chatLanguage):null, false, 'MarkdownV2');
                    $bot->sendPhoto($game->users()->fromTeamRole('b', 'master')->first()->id, $masterPhoto, null, null, ($game->status=='master_b')?self::getMasterKeyboard($chatLanguage):null, false, 'MarkdownV2');
                    unlink($tempMasterImageFileName);
                } catch(Exception $e) {
                    $bot->sendMessage($chatId, AppString::get('error.master_not_registered', null, $chatLanguage));
                }
            } 
            
        } else {
            $text = AppString::get('game.win', [
                'team' => $game::TEAM[$winner]['emoji']
            ], $chatLanguage);

            $bot->sendPhoto($chatId, $masterPhoto, $text, null, null, false, 'MarkdownV2');
            unlink($tempMasterImageFileName);

            $game->stop();
        }

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

    static function addCard($masterImage, $agentsImage, GameCard $card, bool $master) {
        $fontPath = public_path('open-sans.bold.ttf');
        #region calculations
        $y = floor($card->id / 5);
        $x = $card->id - (5*$y);

        $textLen = strlen($card->text);
        if($textLen<9) {
            $fontSize = self::$fontSize;
            $bottomSpace = 0;
        } else if($textLen<11) {
            $fontSize = self::$fontSize-4;
            $bottomSpace = 2;
        } else {
            $fontSize = self::$fontSize-8;
            $bottomSpace = 4;
        }
        $textBox = imagettfbbox($fontSize, 0, $fontPath, $card->text);
        $textWidth = $textBox[2] - $textBox[0];
        //$textHeight = $textBox[1] - $textBox[7]; usuless for now
        $textX = (210 - $textWidth) / 2;
        $textY = 120 - $bottomSpace;
        #endregion

        switch ($card->team) {
            case 'a':
                $colorMaster = Game::TEAM['a']['color'];
                break;
            case 'b':
                $colorMaster = Game::TEAM['b']['color'];
                break;
            case 'x':
                $colorMaster = 'black';
                break;
            
            default:
                $colorMaster = 'white';
                break;
        }

        $masterCardImage = imagecreatefrompng(public_path("images/{$colorMaster}_card.png"));
        $textColor = imagecolorallocate($masterCardImage, 0, 0, 0);
        imagefttext($masterCardImage, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $card->text);

        if($card->revealed) {
            $revealedImage = imagecreatefrompng(public_path("images/revealed_card.png"));
            imagecopy($masterCardImage, $revealedImage, 0, 0, 0, 0, 210, 140);
        } else {
            $agentsCardImage = imagecreatefrompng(public_path("images/white_card.png"));
            $textColor = imagecolorallocate($agentsCardImage, 0, 0, 0);
            imagefttext($agentsCardImage, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $card->text);
        }
        
        if(!is_null($masterImage)) {
            imagecopy($masterImage, $masterCardImage, self::$border+($x*210), self::$border+($y*140), 0, 0, 210, 140);
        }
        if(!is_null($agentsImage)) {
            imagecopy($agentsImage, $agentsCardImage??$masterCardImage, self::$border+($x*210), self::$border+($y*140), 0, 0, 210, 140);
        }
        
    }

}