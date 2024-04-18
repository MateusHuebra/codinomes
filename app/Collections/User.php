<?php

namespace App\Collections;

use App\Services\AppString;
use Illuminate\Database\Eloquent\Collection;

class User extends Collection {

    public function toMentionList($separator = ', ') {
        if($this->count()==0) {
            return null;
        }
        $namesArray = [];
        foreach($this->items as $player) {
            $namesArray[] = AppString::get('game.mention', [
                'name' => AppString::parseMarkdownV2($player->name),
                'id' => $player->id
            ]);
        }
        return implode($separator, $namesArray);
    }
    
}