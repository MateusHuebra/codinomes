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
    const CONFIRM_SKIP = 'yessk';
    const CANCEL_SKIP = 'nosk';
    const CHANGE_COLOR = 'cc';
    const CHANGE_DEFAULT_COLOR = 'cdc';
    const CHANGE_PACK = 'cp';
    const CHANGE_TIMER = 'ct';
    const CHANGE_ADMIN_ONLY = 'cao';
    const MENU = 'mn';
    const SETTINGS = 'st';
    const INFO = 'i';

    //ATTRIBUTES
    const EVENT = 'e';
    const LANGUAGE = 'l';
    const TEXT = 'txt';
    const NUMBER = 'num';
    const PAGE = 'pg';
    const VALUE = 'vlu';
    const FIRST_TIME = 'ft';

    //TYPES
    const ROLE = 'r';
    const TEAM = 't';
    const MASTER = 'm';
    const AGENT = 'a';

    //PACKS
    const PACKS = 'pk';
    const PACKS_ACTIVED = 'pka';
    const PACKS_OFFICIAL = 'pko';
    const PACKS_USERS = 'pku';
    const PACKS_MINE = 'pkm';

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