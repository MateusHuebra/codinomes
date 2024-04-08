<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\File;
use TelegramBot\Api\Types\Message;

class AppString {
    
    const RESERVED_CHARACTERS = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    const ESCAPED_CHARACTERS = ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'];
    
    public static $language = 'pt-br';
    public static $allLanguages = ['pt-br', 'en'];

    static function get(string $path, array $variables = null, $language = null) {
        if(!$language) {
            $language = self::$language;
        }
        $json = File::get(resource_path('strings/'.$language.'.json'));
        $string = json_decode($json);

        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if(!property_exists($string, $key)) {
                return '- string not found: '.$path;
            }
            $string = $string->$key;
        }

        $variations = count($string);
        $string = $string[rand(0, $variations-1)];
        
        if($variables) {
            foreach ($variables as $index => $variable) {
                $string = str_replace('{'.$index.'}', $variable, $string);
            }
        }

        return $string;

    }

    static function parseMarkdownV2($string) {
        return str_replace(self::RESERVED_CHARACTERS, self::ESCAPED_CHARACTERS, $string);
    }

    static function setLanguage(Message $message) {
        $chat = $message->getChat();
        if($chat->getType()==='private') {
            self::$language = User::find($chat->getId())->language??self::$language;
        } else if($chat->getType()==='supergroup') {
            self::$language = Chat::find($message->getChat()->getId())->language??User::find($chat->getId())->language??self::$language;
        }
        
    }

    static function getAllLanguages() {
        if(isset(self::$allLanguages)) {
            return self::$allLanguages;
        }
        //TODO get languages automatically
    }

}