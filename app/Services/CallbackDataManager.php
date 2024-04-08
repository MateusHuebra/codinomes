<?php

namespace App\Services;

class CallbackDataManager {

    const SEPARATOR = ';';
    const DEFINER = '=';

    //EVENTS
    const SET_LANGUAGE = 'sl';

    //ATTRIBUTES
    const EVENT = 'e';
    const LANGUAGE = 'l';
    const TYPE = 't';
    const FIRST_TIME = 'ft';
    const USER_ID = 'ui';
    const CHAT_ID = 'ci';

    //TYPES
    const USER = 'u';
    const CHAT = 'c';

    const TRUE = 1;

    static function toString(array $dataArray) : String {
        $stringArray = [];
        foreach ($dataArray as $key => $value) {
            $stringArray[] = $key.self::DEFINER.$value;
        }
        return implode(self::SEPARATOR, $stringArray);
    }

    static function toArray(string $dataString) : Array {
        $dataArray = [];
        $stringsArray = explode(self::SEPARATOR, $dataString);
        foreach ($stringsArray as $string) {
            $dataUnity = explode(self::DEFINER, $string);
            $dataArray[$dataUnity[0]] = $dataUnity[1];
        }
        return $dataArray;
    }

}