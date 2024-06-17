<?php

namespace App\Actions;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Help implements Action {

    private $type;

    public function __construct(string $type = 'base') {
        $this->type = $type;
    }

    public function run(Update $update, BotApi $bot) : Void {
        if($this->type == 'modes') {
            $text = $this->modesHelp();
        } else {
            $text = AppString::get('help.'.$this->type);
        }
        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

    private function modesHelp() {
        $text = '';
        foreach(array_keys(Game::MODES) as $mode) {
            $text.= '\- \- \- \- \- *'.AppString::getParsed('mode.'.$mode).'* '.Game::MODES[$mode];
            $text.= PHP_EOL.AppString::getParsed('mode.'.$mode.'_info').PHP_EOL.PHP_EOL;
        }
        $text = str_replace(PHP_EOL, PHP_EOL.'>', $text);
        return '**>'.$text.'||';
    }

}