<?php

namespace App\Collections;

use App\Services\AppString;
use Illuminate\Database\Eloquent\Collection;

class User extends Collection {

    public function getStringList(bool $mention, $separator = ', ') {
        if($this->count()==0) {
            return null;
        }
        $namesArray = [];
        foreach($this->items as $player) {
            if($mention) {
                $namesArray[] = AppString::get('game.mention', [
                    'name' => AppString::parseMarkdownV2($player->name),
                    'id' => $player->id
                ]);
            } else {
                $namesArray[] = AppString::parseMarkdownV2($player->name);
            }
        }
        return implode($separator, $namesArray);
    }
    
}