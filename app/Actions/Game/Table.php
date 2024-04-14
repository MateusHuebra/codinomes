<?php

namespace App\Actions\Game;
use App\Actions\Action;
use App\Models\Game;
use App\Models\GameCard;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use CURLFile;
use Exception;
use GdImage;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Table implements Action {
    
    static $border = 15;
    static $fontSize = 23;

    public function run($update, BotApi $bot) : Void {

    }

    static function send(Game $game, BotApi $bot, string $action = null, int $messageId = null) {
        $backgroundColor = ($game->status=='master_a' || $game->status=='agents_a' ? 'purple' : 'orange');
        $masterImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        $agentsImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));

        $cards = $game->cards;
        foreach($cards as $card) {
            self::addCard($masterImage, $agentsImage, $card, true);
        }

        $tempMasterImageFileName = tempnam(sys_get_temp_dir(), 'm_image_');
        $tempAgentsImageFileName = tempnam(sys_get_temp_dir(), 'a_image_');
        imagepng($masterImage, $tempMasterImageFileName);
        imagepng($agentsImage, $tempAgentsImageFileName);
        
        imagedestroy($masterImage);
        imagedestroy($agentsImage);

        $masterPhoto = new CURLFile($tempMasterImageFileName,'image/png','master');
        $agentsPhoto = new CURLFile($tempAgentsImageFileName,'image/png','agents');

        $keyboard = self::getKeyboard($game->status);
        switch ($game->status) {
            case 'master_a':
                $role = AppString::get('game.master');
                $team = Game::A_EMOJI;
                $playersList = $game->users()->fromTeamRole('a', 'master')->get()->toMentionList();
                break;
            case 'agents_a':
                $role = AppString::get('game.agents');
                $team = Game::A_EMOJI;
                $playersList = $game->users()->fromTeamRole('a', 'agent')->get()->toMentionList();
                break;
            case 'master_b':
                $role = AppString::get('game.master');
                $team = Game::B_EMOJI;
                $playersList = $game->users()->fromTeamRole('b', 'master')->get()->toMentionList();
                break;
            case 'agents_b':
                $role = AppString::get('game.agents');
                $team = Game::B_EMOJI;
                $playersList = $game->users()->fromTeamRole('b', 'agent')->get()->toMentionList();
                break;
        }
        
        $text = AppString::get('game.turn', [
            'role' => $role,
            'team' =>  $team,
            'players' => $playersList
        ]);

        $bot->sendPhoto($game->chat_id, $agentsPhoto, $text, null, $keyboard, false, 'MarkdownV2');
        try{
            $bot->sendPhoto($game->users()->fromTeamRole('a', 'master')->first()->id, $masterPhoto, null, null, ($game->status=='master_a')?self::getMasterKeyboard():null, false, 'MarkdownV2');
            $bot->sendPhoto($game->users()->fromTeamRole('b', 'master')->first()->id, $masterPhoto, null, null, ($game->status=='master_b')?self::getMasterKeyboard():null, false, 'MarkdownV2');
        } catch(Exception $e) {
            $bot->sendMessage($game->chat_id, AppString::get('error.master_not_registered'));
        }

        unlink($tempMasterImageFileName);
        unlink($tempAgentsImageFileName);
    }

    static function getKeyboard(string $status) {
        if($status=='master_a' || $status=='master_b') {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.give_hint'),
                        'url' => 't.me/CodinomesBot'
                    ]
                ]
            ]);
        } else {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.choose_card'),
                        'switch_inline_query_current_chat' => ''
                    ]
                ]
            ]);
        }
    }
    
    static function getMasterKeyboard() {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('game.give_hint'),
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
                $colorMaster = Game::A_COLOR;
                break;
            case 'b':
                $colorMaster = Game::B_COLOR;
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
        
        imagecopy($masterImage, $masterCardImage, self::$border+($x*210), self::$border+($y*140), 0, 0, 210, 140);
        imagecopy($agentsImage, $agentsCardImage??$masterCardImage, self::$border+($x*210), self::$border+($y*140), 0, 0, 210, 140);
    }

}