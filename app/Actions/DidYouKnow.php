<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class DidYouKnow implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $chat = $update->findChat();
        if(!$chat) {
            return;
        }

        $text = self::getText($chat->language);
        $keyboard = self::getKeyboard($chat->language);

        $bot->editMessageText($chat->id, $update->getMessageId(), $text, 'MarkdownV2', false, $keyboard);
    }

    public static function getText(string $language) : String {
        $text = '*'.AppString::get('did_you_know.title', null, $language).'*';
        $text.= PHP_EOL.PHP_EOL.AppString::getParsed('did_you_know.text', null, $language);
        return $text;
    }
    
    public static function getKeyboard(string $language) : InlineKeyboardMarkup {
        return new InlineKeyboardMarkup([[[
            'text' => AppString::get('did_you_know.randomize', null, $language),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::DID_YOU_KNOW
            ])
        ]]]);
    }

}