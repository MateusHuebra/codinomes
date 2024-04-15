<?php

namespace App\Services;

class CallbackDataManager {

    const SEPARATOR = ';';
    const DEFINER = '=';

    //EVENTS
    const SET_LANGUAGE = 'sl';
    const SELECT_TEAM_AND_ROLE = 'tr';
    const LEAVE_GAME = 'lg';
    const START_GAME = 'sg';
    const IGNORE = 'ig';
    const HINT = 'hi';
    const GUESS = 'gu';
    const SKIP = 'sk';
    const CHANGE_COLOR = 'cc';

    //ATTRIBUTES
    const EVENT = 'e';
    const LANGUAGE = 'l';
    const TEXT = 'txt';
    const NUMBER = 'num';
    const FIRST_TIME = 'ft';
    const GAME_ID = 'gi';

    //TYPES
    const ROLE = 'r';
    const TEAM = 't';
    const MASTER = 'm';
    const AGENT = 'a';

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