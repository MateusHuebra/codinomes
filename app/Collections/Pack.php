<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class Pack extends Collection {

    public function getCards() {
        $usedWords = [];
        foreach($this as $pack) {
            foreach ($pack->cards as $card) {
                if(!in_array($card->text, $usedWords)) {
                    $usedWords[] = $card->text;
                    $models[] = $card;
                }
            }
        }
        return new Collection($models);
    }
    
}