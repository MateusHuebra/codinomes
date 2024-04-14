<?php

namespace App\Actions\Game;
use App\Actions\Action;
use App\Models\Game;
use App\Models\GameCard;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use CURLFile;
use GdImage;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Table implements Action {
    
    static $border = 15;
    static $fontSize = 22;

    public function run($update, BotApi $bot) : Void {

    }

    static function send(Game $game, BotApi $bot, string $action = null, int $messageId = null) {
        $backgroundColor = ($game->status=='master_a' || $game->status=='agents_a' ? 'purple' : 'orange');
        $masterImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));
        $agentsImage = imagecreatefrompng(public_path('images/'.$backgroundColor.'_background.png'));

        $cards = $game->cards;
        foreach($cards as $card) {
            self::addCard($masterImage, $card, true);
            self::addCard($agentsImage, $card, false);
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

        $bot->sendPhoto(env('TG_MY_ID'), $masterPhoto, null, null, $keyboard, false, 'MarkdownV2');
        $bot->sendPhoto($game->chat_id, $agentsPhoto, $text, null, $keyboard, false, 'MarkdownV2');

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

    static function addCard($image, GameCard $card, bool $master) {
        $y = floor($card->id / 5);
        $x = $card->id - (5*$y);

        if($master || $card->revealed) {
            switch ($card->team) {
                case 'a':
                    $color = Game::A_COLOR;
                    break;
                case 'b':
                    $color = Game::B_COLOR;
                    break;
                case 'x':
                    $color = 'black';
                    break;
                
                default:
                    $color = 'white';
                    break;
            }
        } else {
            $color = 'white';
        }

        $cardImage = imagecreatefrompng(public_path("images/{$color}_card.png"));
        $textColor = imagecolorallocate($cardImage, 0, 0, 0);
        $fontPath = public_path('open-sans.bold.ttf');
        
        $textLen = strlen($card->text);
        if($textLen<10) {
            self::$fontSize = 22;
            $bottomSpace = 0;
        } else {
            self::$fontSize = 16;
            $bottomSpace = 3;
        }
        $textBox = imagettfbbox(self::$fontSize, 0, $fontPath, $card->text);
        $textWidth = $textBox[2] - $textBox[0];
        $textHeight = $textBox[1] - $textBox[7];
        $textX = (210 - $textWidth) / 2;
        $textY = 120 - $bottomSpace;
        imagefttext($cardImage, self::$fontSize, 0, $textX, $textY, $textColor, $fontPath, $card->text);

        if($card->revealed) {
            $revealedImage = imagecreatefrompng(public_path("images/revealed_card.png"));
            imagecopy($cardImage, $revealedImage, 0, 0, 0, 0, 210, 140);
        }
        
        imagecopy($image, $cardImage, self::$border+($x*210), self::$border+($y*140), 0, 0, 210, 140);
    }

}