<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Throwable;
use Illuminate\Support\Facades\File;

class AppString {
    
    const RESERVED_CHARACTERS = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    const ESCAPED_CHARACTERS = ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'];
    
    public static $language = 'pt-br';
    public static $allLanguages = ['pt-br', 'en'];

    static function get(string $path, array $variables = null, $language = null, $parseVariables = false) : String {
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
                if($parseVariables) {
                    $variable = self::parseMarkdownV2($variable);
                }
                $string = str_replace('{'.$index.'}', $variable, $string);
            }
        }

        
        return $string;

    }

    static function getParsed(string $path, array $variables = null, $language = null, $parseVariables = false) : String {
        $string = self::get($path, $variables, $language, $parseVariables);
        return self::parseMarkdownV2($string);
    }

    static function parseMarkdownV2($string) {
        return str_replace(self::RESERVED_CHARACTERS, self::ESCAPED_CHARACTERS, $string);
    }

    static function setLanguage($update) {
        try {
            $chat = $update->getMessage()->getChat();
            if($chat->getType()==='private') {
                self::$language = User::find($chat->getId())->language??self::$language;
            } else if($chat->getType()==='supergroup') {
                self::$language = Chat::find($chat->getId())->language??User::find($chat->getId())->language??self::$language;
            }
        } catch(Throwable $e) {
            try {
                $userId = $update->getFrom()->getId();
                self::$language = User::find($userId)->language??self::$language;
            } catch(Throwable $e) {}
        }
        
    }

    static function getAllLanguages() {
        if(isset(self::$allLanguages)) {
            return self::$allLanguages;
        }
        //TODO get languages automatically
    }

}